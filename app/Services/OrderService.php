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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class OrderService implements ServiceInterface
{
    /**
     * 创建订单（按租户分单）
     *
     * 根据购物车商品按不同租户自动拆分订单，支持多个租户的商品一起下单
     *
     * @param  User  $user  下单用户
     * @param  array<OrderItemDto>  $items  商品项数组，必须实现 OrderItemDto
     * @param  Address|int|null  $address  收货地址对象或地址 ID，null 表示不设置地址
     * @param  string|null  $remark  订单备注
     * @return OrderResult 订单结果，包含所有创建的订单、商品项和地址信息
     * @throws RuntimeException 当商品为空、商品未实现 OrderItemDto、地址无效时抛出
     * @throws Throwable 数据库事务异常
     */
    public function createOrders(User $user, array $items, Address|int|null $address = null, ?string $remark = null): OrderResult
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

        return DB::transaction(function () use ($itemsCollect, $user, $addr, $remark) {
            /** @var Collection $orders */
            $orders = $itemsCollect->groupBy('tenantId')
                ->map(function ($group, $tenantId) use ($user, $addr, $remark) {
                    return $this->createTenantOrder((int) $tenantId, new Collection($group), $user, $addr, $remark);
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
     * 根据传入的商品项集合为该租户创建一个完整的订单，包括订单项和地址信息
     *
     * @param  int  $tenantId  租户 ID
     * @param  Collection<OrderItemDto>  $collect  商品项集合，已按租户分组
     * @param  User  $user  下单用户
     * @param  Address|null  $address  收货地址对象
     * @param  string|null  $remark  订单备注
     * @return Order 创建的订单对象
     * @throws Throwable 数据库操作异常
     */
    private function createTenantOrder(int $tenantId, Collection $collect, User $user, ?Address $address, ?string $remark = null): Order
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
            'remark' => $remark,
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

        $order->tenant->notify(new NewOrderToTenant($order));

        return $order;
    }

    /**
     * 取消订单
     *
     * 将订单状态变更为已取消，并根据商品的扣减库存类型回退库存
     * 仅允许取消待付款状态的订单
     *
     * @param  Order  $order  要取消的订单
     * @param  Authenticatable|null  $user  操作用户，用于记录日志
     * @throws RuntimeException 当订单状态不可取消时抛出
     * @throws Throwable 数据库事务异常
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
     * 处理订单支付成功后的业务逻辑，变更订单状态为已付款并通知租户
     *
     * @param  Order  $order  待支付的订单
     * @param  PaymentOrder  $paymentOrder  支付记录对象
     * @param  Authenticatable|null  $user  操作用户，用于记录日志
     * @throws RuntimeException 当订单状态不可支付时抛出
     * @throws Throwable 数据库事务异常
     */
    public function pay(Order $order, PaymentOrder $paymentOrder, ?Authenticatable $user = null): void
    {
        $this->assertCan($order, OrderStatus::Paid);

        DB::transaction(function () use ($order, $paymentOrder, $user) {
            $oldStatus = $order->status;
            # 修改订单状态
            $order->status = OrderStatus::Paid;
            $order->paid_at = $paymentOrder->paid_at;
            $order->save();

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
     * 订单发货
     *
     * 为订单商品创建物流记录并更新订单状态，支持部分发货
     * 根据已发货商品数量自动判断订单状态：全部发货/部分发货
     *
     * @param  Order  $order  待发货订单
     * @param  array<int>  $itemIds  要发货的商品明细 ID 数组
     * @param  int  $expressId  物流公司 ID
     * @param  string  $expressNo  物流单号
     * @param  Authenticatable|null  $user  操作用户，用于记录日志
     * @throws RuntimeException 当未选择发货商品或订单状态不可发货时抛出
     * @throws Throwable 数据库事务异常
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
     * 删除指定的物流记录，重置关联的商品明细，并根据剩余发货情况重新计算订单状态
     * 如果全部商品都未发货，订单状态将恢复为已付款
     *
     * @param  OrderExpress  $express  要删除的物流记录
     * @param  Authenticatable|null  $user  操作用户，用于记录日志
     * @throws Throwable 数据库事务异常
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
     * 订单签收
     *
     * 确认订单已送达并由用户签收，变更订单状态为已签收
     * 仅允许已发货状态的订单进行签收操作
     *
     * @param  Order  $order  待签收订单
     * @param  Authenticatable|null  $user  操作用户，用于记录日志
     * @throws RuntimeException 当订单状态不可签收时抛出
     * @throws Throwable 数据库事务异常
     */
    public function sign(Order $order, ?Authenticatable $user = null): void
    {
        $this->assertCan($order, OrderStatus::Signed);
        DB::transaction(function () use ($order, $user) {
            $oldStatus = $order->status;
            $order->status = OrderStatus::Signed;
            $order->signed_at = now();
            $order->save();

            // 记录签收日志
            $this->log($order, 'signed', '订单已签收', [
                'status_from' => $oldStatus->value,
                'status_to' => OrderStatus::Signed->value,
                'signed_at' => $order->signed_at->toDateTimeString(),
            ], $user);
        });
    }

    /**
     * 完成订单
     *
     * 标记订单为最终完成状态，通常在用户签收 N 天后执行
     * 完成后订单不再进行任何操作
     *
     * @param  Order  $order  待完成订单
     * @param  Authenticatable|null  $user  操作用户，用于记录日志
     * @throws RuntimeException 当订单状态不可完成时抛出
     * @throws Throwable 数据库事务异常
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
     * 将订单状态变更为备货中，表示商家正在准备商品（拣货、打包等）
     * 仅允许已付款状态的订单进入备货流程
     *
     * @param  Order  $order  待备货订单
     * @param  Authenticatable  $user  操作用户，用于记录日志
     * @throws RuntimeException 当订单状态不可备货时抛出
     * @throws Throwable 数据库事务异常
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
                'status_to' => $order->status->value,
            ], $user);
        });
    }

    /**
     * 修改收货地址
     *
     * 更新订单的收货地址信息，仅允许在已付款或备货中状态下修改
     * 会记录地址变更前后的详细信息
     *
     * @param  Order  $order  要修改地址的订单
     * @param  array<string, mixed>  $data  新地址数据，包含 name、mobile、province_id、city_id、district_id、address 等字段
     * @param  Authenticatable|null  $user  操作用户，用于记录日志
     * @throws RuntimeException 当前订单状态不可修改地址时抛出
     * @throws Throwable 数据库事务异常
     */
    public function modifyAddress(Order $order, array $data, ?Authenticatable $user = null): void
    {
        if (!in_array($order->status, [OrderStatus::Paid, OrderStatus::Preparing], true)) {
            throw new RuntimeException('当前订单状态不可修改地址');
        }

        DB::transaction(function () use ($order, $data, $user) {
            $oldAddress = $order->address->only(['name', 'mobile', 'province_id', 'city_id', 'district_id', 'address']);

            $order->address->update($data);

            // 记录日志
            $this->log($order, 'address_modified', '修改收货地址', [
                'old' => $oldAddress,
                'new' => $data,
            ], $user);
        });
    }

    /**
     * 添加商家备注
     *
     * 为订单添加或更新商家内部备注信息，该备注对用户不可见
     *
     * @param  Order  $order  要添加备注的订单
     * @param  string  $remark  备注内容
     * @param  Authenticatable|null  $user  操作用户，用于记录日志
     * @throws Throwable 数据库事务异常
     */
    public function addSellerRemark(Order $order, string $remark, ?Authenticatable $user = null): void
    {
        DB::transaction(function () use ($order, $remark, $user) {
            $oldRemark = $order->seller_remark;
            $order->seller_remark = $remark;
            $order->save();

            // 记录日志
            $this->log($order, 'seller_remark_added', '添加商家备注', [
                'old' => $oldRemark,
                'new' => $remark,
            ], $user);
        });
    }

    /**
     * 记录订单操作日志
     *
     * 创建订单操作日志记录，保存操作的上下文信息和操作人
     * 如果没有传入用户，尝试从 auth 容器获取当前用户
     * 如果最终没有用户，则记录为系统自动操作
     *
     * @param  Order  $order  订单对象
     * @param  string  $action  操作标识符，如 'created'、'canceled'、'paid' 等
     * @param  string  $remark  操作备注说明
     * @param  array<string, mixed>  $extra  额外的上下文数据
     * @param  Authenticatable|null  $user  操作用户，null 时记录为系统操作
     * @return void
     */
    private function log(
        Order $order,
        string $action,
        string $remark,
        array $extra = [],
        ?Authenticatable $user = null
    ): void {
        // 如果没有传入用户，尝试从 auth 容器获取当前用户
        $user ??= Auth::user();

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
     * 统一的状态验证入口
     *
     * 验证订单是否可以从当前状态变更为目标状态，遵循订单状态机规则
     * 替代传统的 workflow 模式，提供集中式的状态流转控制
     *
     * @param  Order  $order  订单对象
     * @param  OrderStatus  $transition  目标状态
     * @return void
     * @throws RuntimeException 当状态转换不被允许时抛出，包含具体的错误信息
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
