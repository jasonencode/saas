<?php

namespace App\Policies\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Services\Mall\RefundService;

class OrderPolicy extends Policy
{
    protected string $modelName = '订单管理';

    protected string $groupName = '商城管理';

    protected int $platform = 1;

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
        if (!in_array($order->status, [OrderStatus::Pending, OrderStatus::Paid], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('删除', '')]
    public function delete(Authenticatable $user, Order $order): bool
    {
        if (!in_array($order->status, [OrderStatus::Canceled, OrderStatus::Completed], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量删除', '')]
    public function deleteAny(Authenticatable $user, array $recordKeys = []): bool
    {
        if (!empty($recordKeys)) {
            foreach ($recordKeys as $key) {
                $order = Order::where('no', $key)->first();
                if (!$order || !in_array($order->status, [OrderStatus::Canceled, OrderStatus::Completed], true)) {
                    return false;
                }
            }
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('永久删除', '')]
    public function forceDelete(Authenticatable $user, Order $order): bool
    {
        if (!$order->trashed()) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量永久删除', '')]
    public function forceDeleteAny(Authenticatable $user, array $recordKeys = []): bool
    {
        if (!empty($recordKeys)) {
            foreach ($recordKeys as $key) {
                $order = Order::withTrashed()->where('no', $key)->first();
                if (!$order || !$order->trashed()) {
                    return false;
                }
            }
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('恢复', '')]
    public function restore(Authenticatable $user, Order $order): bool
    {
        if (!$order->trashed()) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量恢复', '')]
    public function restoreAny(Authenticatable $user, array $recordKeys = []): bool
    {
        if (!empty($recordKeys)) {
            foreach ($recordKeys as $key) {
                $order = Order::withTrashed()->where('no', $key)->first();
                if (!$order || !$order->trashed()) {
                    return false;
                }
            }
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('取消订单', '')]
    public function orderCancel(Authenticatable $user, Order $order): bool
    {
        if ($order->status !== OrderStatus::Pending) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量禁用', '')]
    public function disableBulk(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量启用', '')]
    public function enableBulk(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量审核', '')]
    public function examineAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('虚拟支付', '')]
    public function virtualPayment(Authenticatable $user, Order $order): bool
    {
        if ($order->status !== OrderStatus::Pending) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('打印拣货单', '')]
    public function orderPrintPickingList(Authenticatable $user, Order $order): bool
    {
        if (!in_array($order->status, [OrderStatus::Paid, OrderStatus::Preparing], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('打印发货单', '')]
    public function orderPrintShipping(Authenticatable $user, Order $order): bool
    {
        if (!in_array($order->status, [OrderStatus::Paid, OrderStatus::Preparing], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('发货', '')]
    public function orderShip(Authenticatable $user, Order $order): bool
    {
        if (!in_array($order->status, [OrderStatus::Paid, OrderStatus::Preparing, OrderStatus::PartiallyShipped], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('签收', '')]
    public function orderSign(Authenticatable $user, Order $order): bool
    {
        if (!in_array($order->status, [OrderStatus::Delivered, OrderStatus::PartiallyShipped], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('完成订单', '')]
    public function orderComplete(Authenticatable $user, Order $order): bool
    {
        if ($order->status !== OrderStatus::Signed) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('退款', '')]
    public function orderRefund(Authenticatable $user, Order $order): bool
    {
        if (!service(RefundService::class)->isOrderRefundable($order)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('排序', '')]
    public function reorder(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('备货', '')]
    public function orderPreparing(Authenticatable $user, Order $order): bool
    {
        if ($order->status !== OrderStatus::Paid) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('修改地址', '')]
    public function orderModifyAddress(Authenticatable $user, Order $order): bool
    {
        if (!in_array($order->status, [OrderStatus::Paid, OrderStatus::Preparing], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('添加备注', '')]
    public function orderAddRemark(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
