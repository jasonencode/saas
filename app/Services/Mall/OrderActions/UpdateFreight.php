<?php

namespace App\Services\Mall\OrderActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * 修改订单运费（pending → 新运费）
 *
 * 入口：OrderService::updateFreight() / UpdateFreight::dispatch()。
 *
 * before：只有待付款订单可以修改运费，且运费不能为负
 * handle：加锁复核后写入新运费
 * log   ：写日志中心 order.freight_updated（含改前/改后金额）
 * after ：空
 */
final class UpdateFreight extends AbstractOperation
{
    /**
     * 修改前的运费（handle 写入、log 读取）
     */
    private ?string $previousFreight = null;

    /**
     * @param  Order  $order  订单
     * @param  Authenticatable  $operator  操作人（后台管理员）
     * @param  float  $freight  新运费
     */
    public function __construct(
        private readonly Order $order,
        private readonly Authenticatable $operator,
        private readonly float $freight,
    ) {}

    /**
     * 前置：待付款状态与运费合法性校验
     */
    public function before(): void
    {
        if ($this->order->status !== OrderStatus::Pending) {
            throw new RuntimeException('当前订单状态不可修改运费');
        }

        if ($this->freight < 0) {
            throw new InvalidArgumentException('运费不能小于 0');
        }
    }

    /**
     * 写入新运费
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            // 加锁复核：支付回调与改运费并发时，不能让已支付订单继续改价
            Order::whereKey($this->order->getKey())->lockForUpdate()->first();
            $this->order->refresh();

            if ($this->order->status !== OrderStatus::Pending) {
                throw new RuntimeException('当前订单状态不可修改运费');
            }

            if ($this->freight < 0) {
                throw new InvalidArgumentException('运费不能小于 0');
            }

            $this->previousFreight = (string) $this->order->freight;
            $this->order->freight = number_format($this->freight, 2, '.', '');
            $this->order->save();

            return true;
        });
    }

    /**
     * 记录修改运费日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('order.freight_updated', '修改运费', $this->operator, $this->order, null, [
            'from' => $this->previousFreight,
            'to' => number_format($this->freight, 2, '.', ''),
        ]);
    }
}
