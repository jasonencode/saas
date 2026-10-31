<?php

namespace App\Services\Mall\OrderActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * 修改订单收货地址
 *
 * 入口：OrderService::modifyAddress() / ModifyAddress::dispatch()。
 *
 * before：仅已支付、备货中订单可修改，且订单存在收货信息
 * handle：写入订单地址快照
 * log   ：写日志中心 order.address_updated
 * after ：空
 *
 * 地址写的是订单快照，不改用户地址簿；发货后不再允许修改。
 */
final class ModifyAddress extends AbstractOperation
{
    /**
     * 修改前的地址快照（供日志记录）
     *
     * @var array<string, mixed>|null
     */
    private ?array $previousAddress = null;

    public function __construct(
        private readonly Order $order,
        private readonly array $data,
        private readonly Authenticatable $operator,
    ) {}

    /**
     * 前置：状态与收货信息校验
     */
    public function before(): void
    {
        if (!in_array($this->order->status, [OrderStatus::Paid, OrderStatus::Preparing], true)) {
            throw new RuntimeException('当前订单状态不可修改地址');
        }

        if (!$this->order->address) {
            throw new RuntimeException('订单收货信息不存在');
        }
    }

    /**
     * 写入订单地址快照
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            // 加锁复核：发货流程与改地址并发时，避免改到已发货订单
            Order::whereKey($this->order->getKey())->lockForUpdate()->first();
            $this->order->refresh();

            if (!in_array($this->order->status, [OrderStatus::Paid, OrderStatus::Preparing], true)) {
                throw new RuntimeException('当前订单状态不可修改地址');
            }

            if (!$this->order->address) {
                throw new RuntimeException('订单收货信息不存在');
            }

            $this->previousAddress = $this->order->address->only(['name', 'mobile', 'province_id', 'city_id', 'district_id', 'address']);

            $this->order->address->update($this->data);

            return true;
        });
    }

    /**
     * 记录修改地址日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('order.address_updated', '修改收货地址', $this->operator, $this->order, null, [
            'old' => $this->previousAddress,
            'new' => $this->data,
        ]);
    }
}
