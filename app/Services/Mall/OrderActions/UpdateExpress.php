<?php

namespace App\Services\Mall\OrderActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Express;
use App\Models\Mall\Order;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * 修改已发货订单的物流信息（partially / delivered / signed）
 *
 * 入口：OrderService::updateExpress() / UpdateExpress::dispatch()。
 *
 * before：状态必须是部分发货、已发货或已签收，且存在可修改的发货记录
 * handle：加锁重读订单与发货记录，逐条覆盖物流公司与单号，只落有变化的记录
 * log   ：写日志中心 order.express_updated（含改前/改后明细）
 * after ：空
 *
 * 参数格式：[发货记录 ID => ['express_id' => 9, 'express_no' => 'SF123'], ...]
 */
final class UpdateExpress extends AbstractOperation
{
    /**
     * 本次实际变更的物流记录（handle 写入、log 读取）
     *
     * @var array<int, array<string, mixed>>
     */
    private array $changes = [];

    /**
     * @param  Order  $order  订单
     * @param  array<int|string, array{express_id?: int|string, express_no?: string}>  $expresses  以发货记录 ID 为键的新物流信息
     * @param  Authenticatable  $operator  操作人（后台管理员）
     */
    public function __construct(
        private readonly Order $order,
        private readonly array $expresses,
        private readonly Authenticatable $operator,
    ) {}

    /**
     * 前置：状态与发货记录校验
     */
    public function before(): void
    {
        $this->assertUpdatable($this->order);

        if ($this->expresses === []) {
            throw new InvalidArgumentException('请至少填写一条物流信息');
        }
    }

    /**
     * 写入新物流信息
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            Order::whereKey($this->order->getKey())->lockForUpdate()->first();
            $this->order->refresh();

            $this->assertUpdatable($this->order);

            $records = $this->order->shippings()
                ->whereIn('id', array_keys($this->expresses))
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($records->isEmpty()) {
                throw new RuntimeException('订单没有可修改的发货记录');
            }

            foreach ($records as $record) {
                $input = $this->expresses[$record->getKey()] ?? null;

                if (blank($input)) {
                    continue;
                }

                $expressId = (int) ($input['express_id'] ?? 0);
                $expressNo = trim((string) ($input['express_no'] ?? ''));

                if ($expressId <= 0 || $expressNo === '') {
                    throw new InvalidArgumentException('物流公司与物流单号不能为空');
                }

                if (!Express::whereKey($expressId)->exists()) {
                    throw new InvalidArgumentException('物流公司不存在');
                }

                if ((int) $record->express_id === $expressId && $record->express_no === $expressNo) {
                    continue;
                }

                $this->changes[] = [
                    'id' => $record->getKey(),
                    'express' => [
                        'from' => $record->express?->name,
                        'to' => Express::find($expressId)?->name,
                    ],
                    'express_no' => [
                        'from' => $record->express_no,
                        'to' => $expressNo,
                    ],
                ];

                $record->express_id = $expressId;
                $record->express_no = $expressNo;
                $record->save();
            }

            return $this->changes !== [];
        });
    }

    /**
     * 记录修改物流信息日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('order.express_updated', '修改物流信息', $this->operator, $this->order, null, [
            'changes' => $this->changes,
        ]);
    }

    /**
     * 状态校验（before 与锁内复核共用）
     */
    private function assertUpdatable(Order $order): void
    {
        if (!in_array($order->status, [OrderStatus::PartiallyShipped, OrderStatus::Delivered, OrderStatus::Signed], true)) {
            throw new RuntimeException('当前订单状态不可修改物流信息');
        }
    }
}
