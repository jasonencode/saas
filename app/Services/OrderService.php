<?php

namespace App\Services;

use App\Contracts\Authenticatable;
use App\Contracts\ServiceInterface;
use App\Dtos\Order\OrderItemDto;
use App\Dtos\Order\OrderResult;
use App\Enums\DeductStockType;
use App\Enums\OrderStatus;
use App\Events\OrderCanceled;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderExpress;
use App\Models\PaymentOrder;
use App\Models\User;
use App\Notifications\NewOrderToTenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class OrderService implements ServiceInterface
{
    /**
     * 记录订单操作日志
     *
     * @param  Order  $order  订单实例
     * @param  string  $action  操作标识
     * @param  string  $remark  操作说明
     * @param  array  $extra  额外上下文数据
     * @param  Authenticatable|null  $user  操作用户（不传则自动获取当前用户）
     */
    protected function log(
        Order $order,
        string $action,
        string $remark,
        array $extra = [],
        ?Authenticatable $user = null
    ): void {
        // 如果没有传入用户，尝试从 auth 容器获取当前用户
        $user ??= auth()->user();

        $context = array_merge([
            'action' => $action,
            'remark' => $remark,
        ], $extra);

        // 如果最终还是没有用户，只记录日志但不关联用户
        if ($user) {
            $order->logs()->create([
                'user' => $user,
                'context' => $context,
            ]);
        } else {
            // 记录系统日志（无关联用户）
            $order->logs()->create([
                'context' => array_merge($context, [
                    'system' => true,
                    'note' => '系统自动操作或用户未登录',
                ]),
            ]);
        }
    }

    /**
     * 创建订单（按租户分单）
     *
     * @param  User  $user  操作用户
     * @param  array<OrderItemDto>  $items
     *
     * @throws Throwable
     */
    public function createOrders(User $user, array $items, Address|int|null $address = null): OrderResult
    {
        if (blank($items)) {
            throw new RuntimeException('订单无商品');
        }
        foreach ($items as $item) {
            if (!($item instanceof OrderItemDto)) {
                throw new RuntimeException('商品必须实现 OrderItemDto 类');
            }
        }

        $addr = null;
        if ($address instanceof Address) {
            if ($address->user->isNot($user)) {
                throw new RuntimeException('地址不正确');
            }
            $addr = $address;
        } elseif (is_numeric($address)) {
            $addr = Address::find($address);
            if (!$addr || $addr->user->isNot($user)) {
                throw new RuntimeException('地址不正确');
            }
        }

        $itemsCollect = new Collection($items);

        return DB::transaction(function () use ($itemsCollect, $user, $addr) {
            $orders = $itemsCollect->groupBy('tenantId')
                ->map(function ($group, $tenantId) use ($user, $addr) {
                    return $this->createTenantOrder((int) $tenantId, $group, $user, $addr);
                });

            return new OrderResult(
                orders: $orders,
                items: $itemsCollect,
                address: $addr,
            );
        });
    }

    /**
     * 为单个租户创建订单
     *
     * @param  int  $tenantId
     * @param  Collection<OrderItemDto>  $collect
     * @param  User  $user
     * @param  Address|null  $address
     * @return Order
     */
    private function createTenantOrder(int $tenantId, Collection $collect, User $user, ?Address $address): Order
    {
        $amount = $collect->reduce(function ($total, OrderItemDto $item) {
            return bcadd($total, $item->getAmount(), 2);
        }, '0.00');

        $freight = $collect->reduce(function ($total, OrderItemDto $item) use ($address) {
            return bcadd($total, $item->getFreight($address), 2);
        }, '0.00');

        $order = Order::create([
            'tenant_id' => $tenantId,
            'user' => $user,
            'amount' => $amount,
            'freight' => $freight,
        ]);

        foreach ($collect as $item) {
            $order->items()->create([
                'product_id' => $item->sku->product_id,
                'sku_id' => $item->sku->id,
                'product_name' => $item->sku->product->name,
                'sku_name' => $item->sku->name,
                'qty' => $item->qty,
                'price' => $item->price,
                'remark' => $item->remark,
            ]);

            if ($item->product->deduct_stock_type === DeductStockType::Ordered) {
                $item->sku->stocks -= $item->qty;
                $item->sku->save();
            }
        }

        if ($address) {
            $order->address()->create([
                'address' => $address,
            ]);
        }

        // 记录订单创建日志
        $this->log($order, 'created', '订单创建成功', [
            'user_id' => $user->id,
            'username' => $user->username,
            'items_count' => $collect->count(),
            'amount' => $order->total_amount,
        ], $user);

        return $order;
    }

    /**
     * 取消订单
     *
     * @param  Authenticatable|null  $user  操作用户
     *
     * @throws Throwable
     */
    public function cancel(Order $order, ?Authenticatable $user = null): void
    {
        DB::transaction(function () use ($order, $user) {
            $this->assertCan($order, OrderStatus::Canceled);

            foreach ($order->items as $item) {
                if ($item->product->deduct_stock_type === DeductStockType::Ordered) {
                    $item->sku->stocks += $item->qty;
                    $item->sku->save();
                }
            }

            OrderCanceled::dispatch($order);

            $order->status = OrderStatus::Canceled;
            $order->save();

            // 记录取消日志
            $this->log($order, 'canceled', '订单已取消', [
                'status_from' => OrderStatus::Pending->value,
                'status_to' => OrderStatus::Canceled->value,
            ], $user);
        });
    }

    /**
     * 支付订单
     *
     * @param  Authenticatable|null  $user  操作用户
     *
     * @throws Throwable
     */
    public function pay(Order $order, PaymentOrder $paymentOrder, ?Authenticatable $user = null): void
    {
        DB::transaction(function () use ($order, $paymentOrder, $user) {
            $this->assertCan($order, OrderStatus::Paid);

            $oldStatus = $order->status;
            $order->paid($paymentOrder);

            // 记录支付日志
            $this->log($order, 'paid', '订单已支付', [
                'status_from' => $oldStatus->value,
                'status_to' => OrderStatus::Paid->value,
                'payment_order_id' => $paymentOrder->id,
                'paid_at' => $order->paid_at?->toDateTimeString(),
            ], $user);

            $order->tenant->notify(new NewOrderToTenant($order));
        });
    }

    /**
     * 发货
     *
     * @param  array  $itemIds  发货商品明细 ID 列表
     * @param  int  $expressId  物流公司 ID
     * @param  string  $expressNo  物流单号
     * @param  Authenticatable|null  $user  操作用户
     *
     * @throws Throwable
     */
    public function deliver(Order $order, array $itemIds, int $expressId, string $expressNo, ?Authenticatable $user = null): void
    {
        DB::transaction(function () use ($order, $itemIds, $expressId, $expressNo, $user) {
            $this->assertCan($order, OrderStatus::Delivered);

            $items = $order->items()->whereIn('id', $itemIds)->get();
            if ($items->isEmpty()) {
                throw new RuntimeException('未选择发货商品');
            }

            // 创建物流记录并记录地址快照
            $express = $order->expresses()->create([
                'express_id' => $expressId,
                'express_no' => $expressNo,
                'delivery_at' => now(),
            ]);

            if ($order->address) {
                $express->setAddress($order->address);
                $express->save();
            }

            // 关联商品明细
            $order->items()->whereIn('id', $itemIds)->update([
                'order_express_id' => $express->id,
            ]);

            // 判断是否全部发货
            $totalItems = $order->items()->count();
            $shippedItems = $order->items()->whereNotNull('order_express_id')->count();

            $oldStatus = $order->status;
            $order->status = $shippedItems >= $totalItems ? OrderStatus::Delivered : OrderStatus::PartiallyShipped;
            $order->save();

            // 记录发货日志
            $this->log($order, 'delivered', '订单商品已发货', [
                'express_id' => $expressId,
                'express_no' => $expressNo,
                'items_count' => $items->count(),
                'status_from' => $oldStatus->value,
                'status_to' => $order->status->value,
                'is_full' => $order->status === OrderStatus::Delivered,
            ], $user);
        });
    }

    /**
     * 删除发货记录并调整订单状态
     *
     * @param  OrderExpress  $express  要删除的物流记录
     * @param  Authenticatable|null  $user  操作用户
     *
     * @throws Throwable
     */
    public function deleteExpress(OrderExpress $express, ?Authenticatable $user = null): void
    {
        DB::transaction(static function () use ($express, $user) {
            $order = $express->order;
            $oldStatus = $order->status;

            // 获取该物流记录关联的商品明细
            $itemsToReset = $order->items()
                ->where('order_express_id', $express->id)
                ->get();

            // 删除物流记录
            $express->delete();

            // 重置已删除物流记录的商品明细
            if ($itemsToReset->isNotEmpty()) {
                $order->items()
                    ->whereIn('id', $itemsToReset->pluck('id'))
                    ->update(['order_express_id' => null]);
            }

            // 重新计算订单状态
            $totalItems = $order->items()->count();
            $shippedItems = $order->items()->whereNotNull('order_express_id')->count();

            if ($shippedItems === 0) {
                // 全部未发货，如果当前是 Delivered 或 PartiallyShipped，恢复到 Paid
                if (in_array($order->status, [OrderStatus::Delivered, OrderStatus::PartiallyShipped], true)) {
                    $order->status = OrderStatus::Paid;
                }
            } elseif ($shippedItems < $totalItems) {
                // 部分发货
                $order->status = OrderStatus::PartiallyShipped;
            } else {
                // 全部发货
                $order->status = OrderStatus::Delivered;
            }

            $order->save();

            // 记录删除物流日志
            $service = app(static::class);
            $service->log($order, 'express_deleted', '发货记录已删除', [
                'status_from' => $oldStatus->value,
                'status_to' => $order->status->value,
                'express_id' => $express->id,
                'express_no' => $express->express_no,
                'reset_items_count' => $itemsToReset->count(),
            ], $user);
        });
    }

    /**
     * 签收
     *
     * @param  Authenticatable|null  $user  操作用户
     *
     * @throws Throwable
     */
    public function sign(Order $order, ?Authenticatable $user = null): void
    {
        DB::transaction(function () use ($order, $user) {
            $this->assertCan($order, OrderStatus::Signed);

            $oldStatus = $order->status;
            $order->status = OrderStatus::Signed;
            $order->save();

            // 记录签收日志
            $this->log($order, 'signed', '订单已签收', [
                'status_from' => $oldStatus->value,
                'status_to' => OrderStatus::Signed->value,
            ], $user);
        });
    }

    /**
     * 完成
     *
     * @param  Authenticatable|null  $user  操作用户
     *
     * @throws Throwable
     */
    public function complete(Order $order, ?Authenticatable $user = null): void
    {
        DB::transaction(function () use ($order, $user) {
            $this->assertCan($order, OrderStatus::Completed);

            $oldStatus = $order->status;
            $order->status = OrderStatus::Completed;
            $order->save();

            // 记录完成日志
            $this->log($order, 'completed', '订单已完成', [
                'status_from' => $oldStatus->value,
                'status_to' => OrderStatus::Completed->value,
            ], $user);
        });
    }

    /**
     * 订单备货
     *
     * @param  Authenticatable  $user  操作用户
     *
     * @throws Throwable
     */
    public function preparing(Order $order, Authenticatable $user): void
    {
        $this->assertCan($order, OrderStatus::Preparing);

        DB::transaction(function () use ($order, $user) {
            $oldStatus = $order->status;
            $order->status = OrderStatus::Preparing;
            $order->save();

            // 记录备货日志
            $this->log($order, 'preparing', '订单开始备货', [
                'status_from' => $oldStatus->value,
                'status_to' => OrderStatus::Preparing->value,
            ], $user);
        });
    }

    /**
     * 统一的状态验证入口（替代 workflow 的 can/apply）
     */
    private function assertCan(Order $order, OrderStatus $transition): void
    {
        $current = $order->status;

        $ok = match ($transition) {
            OrderStatus::Canceled, OrderStatus::Paid => $current === OrderStatus::Pending,
            OrderStatus::Preparing => $current === OrderStatus::Paid,
            OrderStatus::Delivered, OrderStatus::PartiallyShipped => in_array($current, [OrderStatus::Paid, OrderStatus::Preparing, OrderStatus::PartiallyShipped], true),
            OrderStatus::Signed => in_array($current, [OrderStatus::Delivered, OrderStatus::PartiallyShipped], true),
            OrderStatus::Completed => $current === OrderStatus::Signed,
            default => false,
        };

        if ($ok) {
            return;
        }

        $message = match ($transition) {
            OrderStatus::Canceled => '订单状态不可取消',
            OrderStatus::Paid => '订单状态不可支付',
            OrderStatus::Delivered => '订单状态不可发货',
            OrderStatus::Signed => '订单状态不可签收',
            OrderStatus::Completed => '订单状态不可完成',
            default => '订单状态不可变更',
        };

        throw new RuntimeException($message);
    }
}
