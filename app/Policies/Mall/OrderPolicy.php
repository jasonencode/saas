<?php

namespace App\Policies\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
use App\Enums\Mall\OrderStatus;
use App\Enums\System\PolicyType;
use App\Models\Mall\Order;
use App\Services\Mall\RefundService;

class OrderPolicy extends Policy
{
    protected string $modelName = '订单管理';

    protected string $groupName = '商城管理';

    protected int $platform = 1;

    #[PolicyName('列表', '', type: PolicyType::Page)]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('详情', '', type: PolicyType::Page)]
    public function view(Authenticatable $user, Order $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('创建', '', type: PolicyType::Page)]
    public function create(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('编辑', '', type: PolicyType::Page)]
    public function update(Authenticatable $user, Order $order): bool
    {
        if (!in_array($order->status, [OrderStatus::Pending, OrderStatus::Paid], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('删除', '', type: PolicyType::Button)]
    public function delete(Authenticatable $user, Order $order): bool
    {
        if (!in_array($order->status, [OrderStatus::Canceled, OrderStatus::Completed], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量删除', '', type: PolicyType::Button)]
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

    #[PolicyName('永久删除', '', type: PolicyType::Button)]
    public function forceDelete(Authenticatable $user, Order $order): bool
    {
        if (!$order->trashed()) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量永久删除', '', type: PolicyType::Button)]
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

    #[PolicyName('恢复', '', type: PolicyType::Button)]
    public function restore(Authenticatable $user, Order $order): bool
    {
        if (!$order->trashed()) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量恢复', '', type: PolicyType::Button)]
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

    #[PolicyName('取消订单', '', type: PolicyType::Button)]
    public function orderCancel(Authenticatable $user, Order $order): bool
    {
        if ($order->status !== OrderStatus::Pending) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量禁用', '', type: PolicyType::Button)]
    public function disableBulk(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量启用', '', type: PolicyType::Button)]
    public function enableBulk(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量审核', '', type: PolicyType::Button)]
    public function examineAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('订单付款', '', type: PolicyType::Button)]
    public function orderPayment(Authenticatable $user, Order $order): bool
    {
        if ($order->status !== OrderStatus::Pending) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('虚拟支付', '', type: PolicyType::Button)]
    public function orderVirtualPayment(Authenticatable $user, Order $order): bool
    {
        if ($order->status !== OrderStatus::Pending) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('打印拣货单', '', type: PolicyType::Button)]
    public function orderPrintPickingList(Authenticatable $user, Order $order): bool
    {
        if (!in_array($order->status, [OrderStatus::Paid, OrderStatus::Preparing], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('打印发货单', '', type: PolicyType::Button)]
    public function orderPrintShipping(Authenticatable $user, Order $order): bool
    {
        if (!in_array($order->status, [OrderStatus::Paid, OrderStatus::Preparing], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('发货', '', type: PolicyType::Button)]
    public function orderShip(Authenticatable $user, Order $order): bool
    {
        if (!in_array($order->status, [OrderStatus::Paid, OrderStatus::Preparing, OrderStatus::PartiallyShipped], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('签收', '', type: PolicyType::Button)]
    public function orderSign(Authenticatable $user, Order $order): bool
    {
        if (!in_array($order->status, [OrderStatus::Delivered, OrderStatus::PartiallyShipped], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('完成订单', '', type: PolicyType::Button)]
    public function orderComplete(Authenticatable $user, Order $order): bool
    {
        if ($order->status !== OrderStatus::Signed) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('退款', '', type: PolicyType::Button)]
    public function orderRefund(Authenticatable $user, Order $order): bool
    {
        if (!service(RefundService::class)->isOrderRefundable($order)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('排序', '', type: PolicyType::Button)]
    public function reorder(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('备货', '', type: PolicyType::Button)]
    public function orderPreparing(Authenticatable $user, Order $order): bool
    {
        if ($order->status !== OrderStatus::Paid) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('修改地址', '', type: PolicyType::Button)]
    public function orderModifyAddress(Authenticatable $user, Order $order): bool
    {
        if (!in_array($order->status, [OrderStatus::Paid, OrderStatus::Preparing], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('添加备注', '', type: PolicyType::Button)]
    public function orderAddRemark(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
