<?php

namespace App\Policies;

use App\Contracts\Authenticatable;
use App\Contracts\PolicyName;
use App\Enums\OrderStatus;
use App\Models\Order;

class OrderPolicy extends MallPolicy
{
    protected string $modelName = '订单管理';

    #[PolicyName('列表', '')]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('详情', '')]
    public function view(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('创建', '')]
    public function create(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('编辑', '')]
    public function update(Authenticatable $user, Order $order): bool
    {
        // 只有待发货状态的订单才能编辑
        if (! in_array($order->status, [OrderStatus::Pending, OrderStatus::Paid], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('删除', '')]
    public function delete(Authenticatable $user, Order $order): bool
    {
        // 只有已取消或已完成状态的订单才能删除
        if (! in_array($order->status, [OrderStatus::Canceled, OrderStatus::Completed], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量删除')]
    public function deleteAny(Authenticatable $user, array $recordKeys = []): bool
    {
        // 批量删除时，检查所有选中的订单是否都符合删除条件
        if (! empty($recordKeys)) {
            foreach ($recordKeys as $key) {
                $order = Order::where('no', $key)->first();
                if (! $order || ! in_array($order->status, [OrderStatus::Canceled, OrderStatus::Completed], true)) {
                    return false;
                }
            }
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('永久删除')]
    public function forceDelete(Authenticatable $user, Order $order): bool
    {
        // 只有软删除的订单才能永久删除
        if (! $order->trashed()) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量永久删除')]
    public function forceDeleteAny(Authenticatable $user, array $recordKeys = []): bool
    {
        // 批量永久删除时，检查所有选中的订单是否都已软删除
        if (! empty($recordKeys)) {
            foreach ($recordKeys as $key) {
                $order = Order::withTrashed()->where('no', $key)->first();
                if (! $order || ! $order->trashed()) {
                    return false;
                }
            }
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('恢复')]
    public function restore(Authenticatable $user, Order $order): bool
    {
        // 只有软删除的订单才能恢复
        if (! $order->trashed()) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量恢复')]
    public function restoreAny(Authenticatable $user, array $recordKeys = []): bool
    {
        // 批量恢复时，检查所有选中的订单是否都已软删除
        if (! empty($recordKeys)) {
            foreach ($recordKeys as $key) {
                $order = Order::withTrashed()->where('no', $key)->first();
                if (! $order || ! $order->trashed()) {
                    return false;
                }
            }
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('取消订单')]
    public function cancel(Authenticatable $user, Order $order): bool
    {
        // 只有待付款状态的订单才能取消
        if ($order->status !== OrderStatus::Pending) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量禁用')]
    public function disableAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量启用')]
    public function enableAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量审核')]
    public function examineAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('虚拟支付')]
    public function virtualPayment(Authenticatable $user, Order $order): bool
    {
        // 只有待付款状态的订单才能进行虚拟支付
        if ($order->status !== OrderStatus::Pending) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('打印拣货单')]
    public function printPickingList(Authenticatable $user, Order $order): bool
    {
        // 只有已付款或备货中状态的订单才能打印拣货单
        if (! in_array($order->status, [OrderStatus::Paid, OrderStatus::Preparing], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('打印发货单')]
    public function printShipping(Authenticatable $user, Order $order): bool
    {
        // 只有已付款或备货中状态的订单才能打印发货单
        if (! in_array($order->status, [OrderStatus::Paid, OrderStatus::Preparing], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('发货')]
    public function ship(Authenticatable $user, Order $order): bool
    {
        // 只有已付款、备货中或部分发货状态的订单才能发货
        if (! in_array($order->status, [OrderStatus::Paid, OrderStatus::Preparing, OrderStatus::PartiallyShipped], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('签收')]
    public function sign(Authenticatable $user, Order $order): bool
    {
        // 只有已发货状态的订单才能签收
        if ($order->status !== OrderStatus::Delivered) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('完成订单')]
    public function complete(Authenticatable $user, Order $order): bool
    {
        // 只有已签收状态的订单才能完成
        if ($order->status !== OrderStatus::Signed) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('退款')]
    public function refund(Authenticatable $user, Order $order): bool
    {
        // 只有已付款、已发货或已签收状态的订单才能退款
        if (! in_array($order->status, [OrderStatus::Paid, OrderStatus::Delivered, OrderStatus::Signed], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('排序')]
    public function reorder(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
