<?php

namespace App\Policies\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
use App\Enums\Mall\RefundStatus;
use App\Models\Mall\Refund;

class RefundPolicy extends Policy
{
    protected string $modelName = '售后订单';

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
    public function update(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('删除', '')]
    public function delete(Authenticatable $user, Refund $refund): bool
    {
        if (!in_array($refund->status, [RefundStatus::Rejected, RefundStatus::Cancelled], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量删除', '')]
    public function deleteAny(Authenticatable $user, array $recordKeys = []): bool
    {
        if (!empty($recordKeys)) {
            $deletableStatuses = [RefundStatus::Rejected, RefundStatus::Cancelled];
            foreach ($recordKeys as $key) {
                $refund = Refund::where('no', $key)->first();
                if (!$refund || !in_array($refund->status, $deletableStatuses, true)) {
                    return false;
                }
            }
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('永久删除', '')]
    public function forceDelete(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量永久删除', '')]
    public function forceDeleteAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('恢复', '')]
    public function restore(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量恢复', '')]
    public function restoreAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('排序', '')]
    public function reorder(Authenticatable $user): bool
    {
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

    #[PolicyName('取消退款', '')]
    public function cancelRefund(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('审核通过', '')]
    public function approveRefund(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('审核驳回', '')]
    public function rejectRefund(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('确认退款', '')]
    public function confirmRefund(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('提交退货物流', '')]
    public function shipReturn(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('确认签收', '')]
    public function confirmReceive(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
