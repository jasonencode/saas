<?php

namespace App\Services\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\ServiceInterface;
use App\Enums\Mall\DeductStockType;
use App\Enums\Mall\OrderLogAction;
use App\Enums\Mall\OrderStatus;
use App\Events\Mall\OrderCanceled;
use App\Events\Mall\OrderCompleted;
use App\Events\Mall\OrderCreated;
use App\Events\Mall\OrderDelivered;
use App\Events\Mall\OrderPaid;
use App\Events\Mall\OrderPartiallyShipped;
use App\Events\Mall\OrderPreparing;
use App\Events\Mall\OrderSigned;
use App\Models\Mall\Delivery;
use App\Models\Mall\Order;
use App\Models\Mall\OrderAddress;
use App\Models\Mall\OrderShipping;
use App\Models\System\Tenant;
use App\Models\User\Address;
use App\Notifications\NewOrderToTenant;
use App\Services\Mall\DTOs\OrderItemDto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class OrderService implements ServiceInterface
{
    /**
     * 从购物车创建订单（按租户拆分）
     *
     * @return Collection<int, Order>
     * @throws \Throwable
     *
     */
    public function createOrders(Authenticatable $user, Collection|array $items, Address|int|null $address = null): Collection
    {
        $itemsCollect = collect($items);

        if ($itemsCollect->isEmpty()) {
            throw new InvalidArgumentException('订单无商品');
        }

        // 按租户分组
        $grouped = $itemsCollect->groupBy(fn (OrderItemDto $item) => $item->tenantId);

        $orders = collect();

        foreach ($grouped as $tenantId => $tenantItems) {
            $tenant = Tenant::find($tenantId);

            if (!$tenant) {
                throw new RuntimeException("租户不存在: $tenantId");
            }

            $orders->push($this->createOrder($tenant, $user, $tenantItems, $address));
        }

        return $orders;
    }

    /**
     * 创建订单
     *
     * @param  Tenant  $tenant  所属租户
     * @param  Authenticatable  $user  下单用户
     * @param  Collection|array  $items  订单商品列表（OrderItemDto 数组）
     * @param  Address|int|null  $address  收货地址（地址对象、地址 ID 或 null）
     * @param  string|null  $remark  订单备注
     *
     * @return Order 创建的订单
     * @throws InvalidArgumentException 商品列表为空或商品类型错误
     *
     * @throws RuntimeException|\Throwable 地址不存在
     */
    public function createOrder(Tenant $tenant, Authenticatable $user, Collection|array $items, Address|int|null $address = null, ?string $remark = null): Order
    {
        $itemsCollect = collect($items);

        if ($itemsCollect->isEmpty()) {
            throw new InvalidArgumentException('订单无商品');
        }
        foreach ($itemsCollect as $item) {
            if (!($item instanceof OrderItemDto)) {
                throw new InvalidArgumentException('选择的商品必须实现 OrderItemDto 类');
            }
        }

        $addr = null;
        if ($address instanceof Address) {
            $addr = $address;
        } elseif (is_numeric($address)) {
            $addr = Address::find($address);
            if (!$addr) {
                throw new RuntimeException('地址不正确');
            }
        }

        return DB::transaction(function () use ($tenant, $user, $itemsCollect, $addr, $remark) {
            $amount = $itemsCollect->reduce(function (string $total, OrderItemDto $item) {
                return bcadd($total, $item->getAmount(), 2);
            }, '0.00');

            $freight = $this->calculateOrderFreight($tenant, $itemsCollect, $addr);

            $order = Order::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->getKey(),
                'amount' => $amount,
                'freight' => $freight,
                'remark' => $remark,
            ]);

            foreach ($itemsCollect as $item) {
                $orderable = $item->orderable;

                $order->items()->create([
                    'orderable_type' => $orderable::class,
                    'orderable_id' => $orderable->getKey(),
                    'orderable_name' => $orderable->getOrderableName(),
                    'qty' => $item->qty,
                    'price' => $item->price,
                    'remark' => $item->remark,
                ]);

                // 库存扣减委托给可订购主体
                if ($orderable->getDeductStockType() === DeductStockType::Ordered) {
                    $orderable->deductStock($item->qty);
                }
            }

            if ($addr) {
                $orderAddress = new OrderAddress;
                $orderAddress->fillFromAddress($addr);
                $order->address()->save($orderAddress);
            }

            $this->log($order, OrderLogAction::Created, '订单创建成功', [
                'items_count' => $itemsCollect->count(),
                'amount' => $order->total_amount,
            ], $user);

            OrderCreated::dispatch($order);

            return $order;
        });
    }

    private function calculateOrderFreight(Tenant $tenant, Collection $items, ?Address $address): string
    {
        $deliveryService = service(DeliveryService::class);

        $provinceId = $address?->province_id ?? null;
        $cityId = $address?->city_id ?? null;
        $districtId = $address?->district_id ?? null;

        $totalFreight = '0.00';

        // 仅 Sku（实体商品）参与运费计算，其他 Orderable 类型免运费
        $deliverableItems = $items->filter(fn (OrderItemDto $item) => $item->orderable instanceof Sku);
        if ($deliverableItems->isEmpty()) {
            return $totalFreight;
        }

        $groupedByDelivery = $deliverableItems->groupBy(function (OrderItemDto $item) {
            return $item->orderable->product->delivery_id ?? 'default';
        });

        foreach ($groupedByDelivery as $deliveryId => $groupItems) {
            $delivery = $deliveryId === 'default'
                ? $deliveryService->getDefaultForTenant($tenant->id)
                : Delivery::find($deliveryId);

            if ($delivery) {
                $freight = $deliveryService->calculateOrderFreight(
                    delivery: $delivery,
                    items: $groupItems,
                    provinceId: $provinceId,
                    cityId: $cityId,
                    districtId: $districtId,
                );
                $totalFreight = bcadd($totalFreight, $freight, 2);
            }
        }

        return $totalFreight;
    }

    private function log(
        Order $order,
        OrderLogAction $action,
        string $remark,
        array $extra = [],
        ?Authenticatable $user = null,
    ): void {
        $order->logs()->create([
            'action' => $action,
            'remark' => $remark,
            'operator_id' => $user->getKey(),
            'context' => $extra,
        ]);
    }

    /**
     * 取消订单
     *
     * @param  Order  $order  订单
     * @param  Authenticatable|null  $user  操作人
     *
     * @throws \Throwable
     */
    public function cancel(Order $order, ?Authenticatable $user = null): void
    {
        DB::transaction(function () use ($order, $user) {
            $this->assertCan($order, OrderStatus::Canceled);

            $order->loadMissing('items.orderable');

            foreach ($order->items as $item) {
                $orderable = $item->orderable;
                if ($orderable && $orderable->getDeductStockType() === DeductStockType::Ordered) {
                    $orderable->restoreStock($item->qty);
                }
            }

            $order->status = OrderStatus::Canceled;
            $order->save();

            OrderCanceled::dispatch($order, $user);

            $this->log($order, OrderLogAction::Canceled, '订单已取消', [
                'status_from' => OrderStatus::Pending->value,
                'status_to' => OrderStatus::Canceled->value,
            ], $user);
        });
    }

    private function assertCan(Order $order, OrderStatus $transition): void
    {
        if ($order->status->canTransitionTo($transition)) {
            return;
        }

        throw new RuntimeException(
            "订单状态不可从「{$order->status->getLabel()}」变更为「{$transition->getLabel()}」",
        );
    }

    /**
     * 订单支付成功
     *
     * @param  Order  $order  订单
     * @param  Authenticatable|null  $user  操作人
     *
     * @throws \Throwable
     */
    public function pay(Order $order, ?Authenticatable $user = null): void
    {
        $this->assertCan($order, OrderStatus::Paid);

        DB::transaction(function () use ($order, $user) {
            $oldStatus = $order->status;
            $order->status = OrderStatus::Paid;
            $order->paid_at = now();
            $order->save();

            $this->log($order, OrderLogAction::Paid, '订单已支付', [
                'status_from' => $oldStatus->value,
                'status_to' => OrderStatus::Paid->value,
                'paid_at' => $order->paid_at->toDateTimeString(),
            ], $user);

            $order->tenant->notify(new NewOrderToTenant($order));

            OrderPaid::dispatch($order, $user);
        });
    }

    /**
     * 订单发货
     *
     * @param  Order  $order  订单
     * @param  array  $itemIds  发货的商品明细 ID 列表
     * @param  int  $expressId  快递公司 ID
     * @param  string  $expressNo  快递单号
     * @param  Authenticatable|null  $user  操作人
     *
     * @throws \Throwable
     */
    public function deliver(Order $order, array $itemIds, int $expressId, string $expressNo, ?Authenticatable $user = null): void
    {
        DB::transaction(function () use ($order, $itemIds, $expressId, $expressNo, $user) {
            $this->assertCan($order, OrderStatus::Delivered);

            $items = $order->items()->whereIn('id', $itemIds)->get();
            if ($items->isEmpty()) {
                throw new InvalidArgumentException('未选择发货商品');
            }

            $express = $order->shippings()->create([
                'express_id' => $expressId,
                'express_no' => $expressNo,
                'delivery_at' => now(),
            ]);

            if ($order->address) {
                $express->setAddress($order->address);
            }

            $order->items()->whereIn('id', $itemIds)->update([
                'order_shipping_id' => $express->id,
            ]);

            $totalItems = $order->items()->count();
            $shippedItems = $order->items()->whereNotNull('order_shipping_id')->count();

            $oldStatus = $order->status;
            $order->status = $shippedItems >= $totalItems ? OrderStatus::Delivered : OrderStatus::PartiallyShipped;
            $order->save();

            $this->log($order, OrderLogAction::Delivered, '订单商品已发货', [
                'express_id' => $expressId,
                'express_no' => $expressNo,
                'items_count' => $items->count(),
                'status_from' => $oldStatus->value,
                'status_to' => $order->status->value,
                'is_full' => $order->status === OrderStatus::Delivered,
            ], $user);

            if ($order->status === OrderStatus::Delivered) {
                OrderDelivered::dispatch($order, $user);
            } else {
                OrderPartiallyShipped::dispatch($order, $user);
            }
        });
    }

    /**
     * 删除发货记录
     *
     * @param  OrderShipping  $express  发货记录
     * @param  Authenticatable|null  $user  操作人
     *
     * @throws \Throwable
     */
    public function deleteExpress(OrderShipping $express, ?Authenticatable $user = null): void
    {
        DB::transaction(function () use ($express, $user) {
            $order = $express->order;
            $oldStatus = $order->status;

            $itemsToReset = $order
                ->items()
                ->where('order_shipping_id', $express->id)
                ->get();

            $express->delete();

            if ($itemsToReset->isNotEmpty()) {
                $order
                    ->items()
                    ->whereIn('id', $itemsToReset->pluck('id'))
                    ->update(['order_shipping_id' => null]);
            }

            $totalItems = $order->items()->count();
            $shippedItems = $order->items()->whereNotNull('order_shipping_id')->count();

            if ($shippedItems === 0) {
                if (in_array($order->status, [OrderStatus::Delivered, OrderStatus::PartiallyShipped], true)) {
                    $order->status = OrderStatus::Paid;
                }
            } elseif ($shippedItems < $totalItems) {
                $order->status = OrderStatus::PartiallyShipped;
            } else {
                $order->status = OrderStatus::Delivered;
            }

            $order->save();

            $this->log($order, OrderLogAction::ExpressDeleted, '发货记录已删除', [
                'status_from' => $oldStatus->value,
                'status_to' => $order->status->value,
                'express_id' => $express->id,
                'express_no' => $express->express_no,
                'reset_items_count' => $itemsToReset->count(),
            ], $user);
        });
    }

    /**
     * 删除订单
     *
     * @param  Order  $order  订单
     * @param  Authenticatable|null  $user  操作人
     *
     * @throws InvalidArgumentException|\Throwable 订单状态不允许删除
     */
    public function delete(Order $order, ?Authenticatable $user = null): void
    {
        if (!in_array($order->status, [OrderStatus::Pending, OrderStatus::Canceled], true)) {
            throw new InvalidArgumentException('当前订单状态不允许删除');
        }

        DB::transaction(function () use ($order, $user) {
            $order->delete();

            $this->log($order, OrderLogAction::Deleted, '订单已删除', [
                'status_from' => $order->status,
            ], $user);
        });
    }

    /**
     * 订单签收
     *
     * @param  Order  $order  订单
     * @param  Authenticatable|null  $user  操作人
     *
     * @throws \Throwable
     */
    public function sign(Order $order, ?Authenticatable $user = null): void
    {
        $this->assertCan($order, OrderStatus::Signed);
        DB::transaction(function () use ($order, $user) {
            $oldStatus = $order->status;
            $order->status = OrderStatus::Signed;
            $order->signed_at = now();
            $order->save();

            $this->log($order, OrderLogAction::Signed, '订单已签收', [
                'status_from' => $oldStatus->value,
                'status_to' => OrderStatus::Signed->value,
                'signed_at' => $order->signed_at->toDateTimeString(),
            ], $user);

            OrderSigned::dispatch($order, $user);
        });
    }

    /**
     * 订单完成
     *
     * @param  Order  $order  订单
     * @param  Authenticatable|null  $user  操作人
     *
     * @throws \Throwable
     */
    public function complete(Order $order, ?Authenticatable $user = null): void
    {
        DB::transaction(function () use ($order, $user) {
            $this->assertCan($order, OrderStatus::Completed);

            $oldStatus = $order->status;
            $order->status = OrderStatus::Completed;
            $order->save();

            $this->log($order, OrderLogAction::Completed, '订单已完成', [
                'status_from' => $oldStatus->value,
                'status_to' => OrderStatus::Completed->value,
            ], $user);

            OrderCompleted::dispatch($order, $user);
        });
    }

    /**
     * 订单开始备货
     *
     * @param  Order  $order  订单
     * @param  Authenticatable  $user  操作人
     *
     * @throws \Throwable
     */
    public function preparing(Order $order, Authenticatable $user): void
    {
        $this->assertCan($order, OrderStatus::Preparing);

        DB::transaction(function () use ($order, $user) {
            $oldStatus = $order->status;
            $order->status = OrderStatus::Preparing;
            $order->save();

            $this->log($order, OrderLogAction::Preparing, '订单开始备货', [
                'status_from' => $oldStatus->value,
                'status_to' => $order->status->value,
            ], $user);

            OrderPreparing::dispatch($order, $user);
        });
    }

    /**
     * 修改订单收货地址
     *
     * @param  Order  $order  订单
     * @param  array  $data  新地址数据
     * @param  Authenticatable|null  $user  操作人
     *
     * @throws RuntimeException|\Throwable 订单状态不可修改地址
     */
    public function modifyAddress(Order $order, array $data, ?Authenticatable $user = null): void
    {
        if (!in_array($order->status, [OrderStatus::Paid, OrderStatus::Preparing], true)) {
            throw new RuntimeException('当前订单状态不可修改地址');
        }

        DB::transaction(function () use ($order, $data, $user) {
            $oldAddress = $order->address->only(['name', 'mobile', 'province_id', 'city_id', 'district_id', 'address']);

            $order->address->update($data);

            $this->log($order, OrderLogAction::AddressModified, '修改收货地址', [
                'old' => $oldAddress,
                'new' => $data,
            ], $user);
        });
    }

    /**
     * 添加商家备注
     *
     * @param  Order  $order  订单
     * @param  string  $remark  备注内容
     * @param  Authenticatable|null  $user  操作人
     *
     * @throws \Throwable
     */
    public function addSellerRemark(Order $order, string $remark, ?Authenticatable $user = null): void
    {
        DB::transaction(function () use ($order, $remark, $user) {
            $oldRemark = $order->seller_remark;
            $order->seller_remark = $remark;
            $order->save();

            $this->log($order, OrderLogAction::SellerRemarkAdded, '添加商家备注', [
                'old' => $oldRemark,
                'new' => $remark,
            ], $user);
        });
    }
}
