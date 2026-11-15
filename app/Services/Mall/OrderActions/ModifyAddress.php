<?php

namespace App\Services\Mall\OrderActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Models\Mall\OrderAddress;
use App\Services\System\EventLogService;
use Illuminate\Support\Arr;
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
     * 前置：状态校验
     */
    public function before(): void
    {
        if (!in_array($this->order->status, [OrderStatus::Paid, OrderStatus::Preparing], true)) {
            throw new RuntimeException('当前订单状态不可修改地址');
        }
    }

    /**
     * 写入订单地址快照
     *
     * 地址不存在时补建，便于修复历史遗留的无收货信息订单。
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

            // 只允许修改收货信息字段，避免调用方把 order_id 等字段带进来改挂地址
            $data = Arr::only($this->data, [
                'name',
                'mobile',
                'province_id',
                'city_id',
                'district_id',
                'address',
            ]);

            $address = $this->order->address;

            $this->previousAddress = $address?->only([
                'name',
                'mobile',
                'province_id',
                'city_id',
                'district_id',
                'address',
            ]);

            if ($address) {
                $address->update($data);
            } else {
                $orderAddress = new OrderAddress;
                $orderAddress->fill($data);
                $this->order->address()->save($orderAddress);
            }

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
