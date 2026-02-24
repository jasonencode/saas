<?php

namespace App\Services;

use App\Dtos\Order\OrderItem;
use App\Dtos\Order\OrderResult;
use App\Enums\DeductStockType;
use App\Enums\OrderStatus;
use App\Events\OrderCanceled;
use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class OrderService
{
    /**
     * 创建订单（按租户分单）
     *
     * @param  User  $user
     * @param  array<OrderItem>  $items
     * @param  Address|int|null  $address
     * @return OrderResult
     * @throws Throwable
     */
    public function createOrders(User $user, array $items, Address|int|null $address = null): OrderResult
    {
        if (blank($items)) {
            throw new RuntimeException('订单无商品');
        }
        foreach ($items as $item) {
            if (!($item instanceof OrderItem)) {
                throw new RuntimeException('商品必须实现 OrderItem 类');
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
            $orders = $itemsCollect->groupBy('tenantId')->map(function ($group, $tenantId) use ($user, $addr) {
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
     * @param  Collection<OrderItem>  $collect
     * @param  User  $user
     * @param  Address|null  $address
     * @return Order
     */
    private function createTenantOrder(int $tenantId, Collection $collect, User $user, ?Address $address): Order
    {
        $amount = $collect->reduce(function ($total, OrderItem $item) {
            return bcadd($total, $item->getAmount(), 2);
        }, '0.00');

        $freight = $collect->reduce(function ($total, OrderItem $item) use ($address) {
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
                'qty' => $item->qty,
                'price' => $item->price,
                'remark' => $item->remark,
            ]);

            if ($item->product->deduct_stock_type == DeductStockType::Ordered) {
                $item->sku->stocks -= $item->qty;
                $item->sku->save();
            }
        }

        if ($address) {
            $order->address()->create([
                'address' => $address,
            ]);
        }

        return $order;
    }

    /**
     * 取消订单
     *
     * @throws Exception|Throwable
     */
    public function cancel(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $this->assertCan($order, 'cancel');

            foreach ($order->items as $item) {
                if ($item->product->deduct_stock_type == DeductStockType::Ordered) {
                    $item->sku->stocks += $item->qty;
                    $item->sku->save();
                }
            }

            OrderCanceled::dispatch($order);

            $order->status = OrderStatus::Canceled;
            $order->save();
        });
    }

    /**
     * 统一的状态验证入口（替代 workflow 的 can/apply）
     *
     * @throws Exception
     */
    private function assertCan(Order $order, string $transition): void
    {
        $current = $order->status;

        $ok = match ($transition) {
            'cancel', 'pay' => $current === OrderStatus::Pending,
            'deliver' => $current === OrderStatus::Paid,
            'sign' => $current === OrderStatus::Delivered,
            'complete' => $current === OrderStatus::Signed,
            default => false,
        };

        if ($ok) {
            return;
        }

        $messages = [
            'cancel' => '订单状态不可取消',
            'pay' => '订单状态不可支付',
            'deliver' => '订单状态不可发货',
            'sign' => '订单状态不可签收',
            'complete' => '订单状态不可完成',
        ];

        throw new RuntimeException($messages[$transition] ?? '订单状态不可变更');
    }

    /**
     * 支付订单
     *
     * @throws Exception|Throwable
     */
    public function pay(Order $order, ?Carbon $paidAt = null): void
    {
        DB::transaction(function () use ($order, $paidAt) {
            $this->assertCan($order, 'pay');

            // 复用模型已有的 paid 行为，确保领域一致性
            $order->paid($paidAt ?? Carbon::now());
        });
    }

    /**
     * 发货
     *
     * @throws Exception|Throwable
     */
    public function deliver(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $this->assertCan($order, 'deliver');

            $order->status = OrderStatus::Delivered;
            $order->save();
        });
    }

    /**
     * 签收
     *
     * @throws Exception|Throwable
     */
    public function sign(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $this->assertCan($order, 'sign');

            $order->status = OrderStatus::Signed;
            $order->save();
        });
    }

    /**
     * 完成
     *
     * @throws Exception|Throwable
     */
    public function complete(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $this->assertCan($order, 'complete');

            $order->status = OrderStatus::Completed;
            $order->save();
        });
    }
}
