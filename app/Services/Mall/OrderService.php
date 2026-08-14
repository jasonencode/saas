<?php

namespace App\Services\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\ServiceInterface;
use App\Enums\Mall\DeductStockType;
use App\Enums\Mall\FulfillmentType;
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
use App\Models\Mall\Sku;
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
     * @param  FulfillmentType  $fulfillmentType  下单所选履约方式，订单内所有商品必须支持
     * @param  Address|int|null  $address  收货地址（地址对象、地址 ID 或 null）
     *
     * @throws \Throwable
     *
     * @return Collection<int, Order>
     */
    public function createOrders(
        Authenticatable $user,
        Collection|array $items,
        FulfillmentType $fulfillmentType = FulfillmentType::Mail,
        Address|int|null $address = null
    ): Collection {
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

            $orders->push($this->createOrder($tenant, $user, $tenantItems, $fulfillmentType, $address));
        }

        return $orders;
    }

    /**
     * 创建订单
     *
     * @param  Tenant  $tenant  所属租户
     * @param  Authenticatable  $user  下单用户
     * @param  Collection|array  $items  订单商品列表（OrderItemDto 数组）
     * @param  FulfillmentType  $fulfillmentType  下单所选履约方式，订单内所有商品必须支持
     * @param  Address|int|null  $address  收货地址（地址对象、地址 ID 或 null）
     * @param  string|null  $remark  订单备注
     *
     * @throws InvalidArgumentException 商品列表为空或商品类型错误
     * @throws RuntimeException|\Throwable 地址不存在或订单项不支持所选履约方式
     *
     * @return Order 创建的订单
     */
    public function createOrder(
        Tenant $tenant,
        Authenticatable $user,
        Collection|array $items,
        FulfillmentType $fulfillmentType = FulfillmentType::Mail,
        Address|int|null $address = null,
        ?string $remark = null
    ): Order {
        $itemsCollect = collect($items);

        if ($itemsCollect->isEmpty()) {
            throw new InvalidArgumentException('订单无商品');
        }
        foreach ($itemsCollect as $item) {
            if (!($item instanceof OrderItemDto)) {
                throw new InvalidArgumentException('选择的商品必须实现 OrderItemDto 类');
            }

            // 校验订单项是否支持所选履约方式，任一不支持则整单拒绝
            if (!$item->orderable->supportsFulfillmentType($fulfillmentType)) {
                throw new RuntimeException(sprintf(
                    '商品[%s]不支持[%s]履约方式',
                    $item->orderable->getOrderableName(),
                    $fulfillmentType->getLabel()
                ));
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

        return DB::transaction(function () use ($tenant, $user, $itemsCollect, $addr, $remark, $fulfillmentType) {
            $amount = $itemsCollect->reduce(function (string $total, OrderItemDto $item) {
                return bcadd($total, $item->getAmount(), 2);
            }, '0.00');

            $freight = $this->calculateOrderFreight($tenant, $itemsCollect, $addr, $fulfillmentType);

            $order = Order::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->getKey(),
                'amount' => $amount,
                'freight' => $freight,
                'fulfillment_type' => $fulfillmentType,
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

            // 下单即建立用户与租户的关联，便于租户后台统计其用户量
            $user->tenants()->syncWithoutDetaching($tenant->getKey());

            $this->log(
                order: $order,
                action: OrderLogAction::Created,
                user: $user,
                remark: '订单创建成功',
                context: [
                    'items_count' => $itemsCollect->count(),
                    'amount' => $order->total_amount,
                ]
            );

            OrderCreated::dispatch($order);

            return $order;
        });
    }

    /**
     * 计算订单运费
     *
     * 仅快递邮寄（mail）履约方式按运费模板计费，门店自提与虚拟商品免运费。
     *
     * @param  Tenant  $tenant  所属租户
     * @param  Collection  $items  订单商品列表
     * @param  Address|null  $address  收货地址
     * @param  FulfillmentType  $fulfillmentType  订单履约方式
     *
     * @return string 运费
     */
    private function calculateOrderFreight(Tenant $tenant, Collection $items, ?Address $address, FulfillmentType $fulfillmentType): string
    {
        // 非快递邮寄履约方式免运费
        if ($fulfillmentType !== FulfillmentType::Mail) {
            return '0.00';
        }

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

    /**
     * 记录订单日志
     *
     * @param  Order  $order  订单
     * @param  OrderLogAction  $action  操作行为
     * @param  Authenticatable  $user  操作人
     * @param  string  $remark  操作说明
     * @param  array  $context  操作上下文
     */
    public function log(
        Order $order,
        OrderLogAction $action,
        Authenticatable $user,
        string $remark,
        array $context = []
    ): void {
        $order->logs()->create([
            'action' => $action,
            'operator' => $user,
            'remark' => $remark,
            'context' => $context,
        ]);
    }

    /**
     * 取消订单
     *
     * @param  Order  $order  订单
     * @param  Authenticatable  $user  操作人
     *
     * @throws \Throwable
     */
    public function cancel(Order $order, Authenticatable $user): void
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

            $this->log(
                order: $order,
                action: OrderLogAction::Canceled,
                user: $user,
                remark: '订单已取消',
                context: [
                    'previous_status' => OrderStatus::Pending->value,
                    'next_status' => OrderStatus::Canceled->value,
                ]
            );
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
     * @param  Authenticatable  $user  操作人
     *
     * @throws \Throwable
     */
    public function pay(Order $order, Authenticatable $user): void
    {
        $this->assertCan($order, OrderStatus::Paid);

        DB::transaction(function () use ($order, $user) {
            $oldStatus = $order->status;
            $order->status = OrderStatus::Paid;
            $order->paid_at = now();
            $order->save();

            $this->log(
                order: $order,
                action: OrderLogAction::Paid,
                user: $user,
                remark: '订单已支付',
                context: [
                    'previous_status' => $oldStatus->value,
                    'next_status' => OrderStatus::Paid->value,
                    'paid_at' => $order->paid_at->toDateTimeString(),
                ]
            );

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
     * @param  Authenticatable  $user  操作人
     *
     * @throws \Throwable
     */
    public function deliver(Order $order, array $itemIds, int $expressId, string $expressNo, Authenticatable $user): void
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

            $this->log(
                order: $order,
                action: OrderLogAction::Delivered,
                user: $user,
                remark: '订单商品已发货',
                context: [
                    'express_id' => $expressId,
                    'express_no' => $expressNo,
                    'items_count' => $items->count(),
                    'previous_status' => $oldStatus->value,
                    'next_status' => $order->status->value,
                    'is_full' => $order->status === OrderStatus::Delivered,
                ]
            );

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
     * @param  Authenticatable  $user  操作人
     *
     * @throws \Throwable
     */
    public function deleteExpress(OrderShipping $express, Authenticatable $user): void
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

            $this->log(
                order: $order,
                action: OrderLogAction::ExpressDeleted,
                user: $user,
                remark: '发货记录已删除',
                context: [
                    'previous_status' => $oldStatus->value,
                    'next_status' => $order->status->value,
                    'express_id' => $express->id,
                    'express_no' => $express->express_no,
                    'reset_items_count' => $itemsToReset->count(),
                ]
            );
        });
    }

    /**
     * 删除订单
     *
     * @param  Order  $order  订单
     * @param  Authenticatable  $user  操作人
     *
     * @throws InvalidArgumentException|\Throwable 订单状态不允许删除
     */
    public function delete(Order $order, Authenticatable $user): void
    {
        if (!in_array($order->status, [OrderStatus::Pending, OrderStatus::Canceled], true)) {
            throw new InvalidArgumentException('当前订单状态不允许删除');
        }

        DB::transaction(function () use ($order, $user) {
            $order->delete();

            $this->log(
                order: $order,
                action: OrderLogAction::Deleted,
                user: $user,
                remark: '订单已删除',
                context: [
                    'previous_status' => $order->status,
                ]
            );
        });
    }

    /**
     * 订单签收
     *
     * @param  Order  $order  订单
     * @param  Authenticatable  $user  操作人
     *
     * @throws \Throwable
     */
    public function sign(Order $order, Authenticatable $user): void
    {
        $this->assertCan($order, OrderStatus::Signed);
        DB::transaction(function () use ($order, $user) {
            $oldStatus = $order->status;
            $order->status = OrderStatus::Signed;
            $order->signed_at = now();
            $order->save();

            $order->shippings()
                ->whereNull('sign_at')
                ->update(['sign_at' => now()]);

            $this->log(
                order: $order,
                action: OrderLogAction::Signed,
                user: $user,
                remark: '订单已签收',
                context: [
                    'previous_status' => $oldStatus->value,
                    'next_status' => OrderStatus::Signed->value,
                    'signed_at' => $order->signed_at->toDateTimeString(),
                ]
            );

            OrderSigned::dispatch($order, $user);
        });
    }

    /**
     * 订单完成
     *
     * @param  Order  $order  订单
     * @param  Authenticatable  $user  操作人
     *
     * @throws \Throwable
     */
    public function complete(Order $order, Authenticatable $user): void
    {
        DB::transaction(function () use ($order, $user) {
            $this->assertCan($order, OrderStatus::Completed);

            $oldStatus = $order->status;
            $order->status = OrderStatus::Completed;
            $order->save();

            $this->log(
                order: $order,
                action: OrderLogAction::Completed,
                user: $user,
                remark: '订单已完成',
                context: [
                    'previous_status' => $oldStatus->value,
                    'next_status' => OrderStatus::Completed->value,
                ]
            );

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

            $this->log(
                order: $order,
                action: OrderLogAction::Preparing,
                user: $user,
                remark: '开始备货',
                context: [
                    'previous_status' => $oldStatus->value,
                    'next_status' => OrderStatus::Preparing->value,
                ]
            );

            OrderPreparing::dispatch($order, $user);
        });
    }

    /**
     * 修改订单收货地址
     *
     * @param  Order  $order  订单
     * @param  array  $data  新地址数据
     * @param  Authenticatable  $user  操作人
     *
     * @throws RuntimeException|\Throwable 订单状态不可修改地址
     */
    public function modifyAddress(Order $order, array $data, Authenticatable $user): void
    {
        if (!in_array($order->status, [OrderStatus::Paid, OrderStatus::Preparing], true)) {
            throw new RuntimeException('当前订单状态不可修改地址');
        }

        DB::transaction(function () use ($order, $data, $user) {
            $oldAddress = $order->address->only(['name', 'mobile', 'province_id', 'city_id', 'district_id', 'address']);

            $order->address->update($data);

            $this->log(
                order: $order,
                action: OrderLogAction::AddressModified,
                user: $user,
                remark: '修改收货地址',
                context: [
                    'old' => $oldAddress,
                    'new' => $data,
                ]
            );
        });
    }

    /**
     * 添加商家备注
     *
     * @param  Order  $order  订单
     * @param  string  $remark  备注内容
     * @param  Authenticatable  $user  操作人
     *
     * @throws \Throwable
     */
    public function addSellerRemark(Order $order, string $remark, Authenticatable $user): void
    {
        DB::transaction(function () use ($order, $remark, $user) {
            $oldRemark = $order->seller_remark;
            $order->seller_remark = $remark;
            $order->save();

            $this->log(
                order: $order,
                action: OrderLogAction::SellerRemarkAdded,
                user: $user,
                remark: '添加商家备注',
                context: [
                    'old' => $oldRemark,
                    'new' => $remark,
                ]
            );
        });
    }
}
