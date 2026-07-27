<?php

namespace App\Policies\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
use App\Enums\System\PolicyType;
use App\Models\Mall\Product;

class ProductPolicy extends Policy
{
    protected string $modelName = '全部商品';

    protected string $groupName = '商城管理';

    #[PolicyName('列表', type: PolicyType::Page)]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('详情', type: PolicyType::Page)]
    public function view(Authenticatable $user, Product $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('创建', type: PolicyType::Page)]
    public function create(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('编辑', type: PolicyType::Page)]
    public function update(Authenticatable $user, Product $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('删除', type: PolicyType::Button)]
    public function delete(Authenticatable $user, Product $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量删除', type: PolicyType::Button)]
    public function deleteAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('永久删除', type: PolicyType::Button)]
    public function forceDelete(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量永久删除', type: PolicyType::Button)]
    public function forceDeleteAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('恢复', type: PolicyType::Button)]
    public function restore(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量恢复', type: PolicyType::Button)]
    public function restoreAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('排序', type: PolicyType::Button)]
    public function reorder(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量禁用', type: PolicyType::Button)]
    public function disableBulk(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量启用', type: PolicyType::Button)]
    public function enableBulk(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量审核', type: PolicyType::Button)]
    public function examineAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('上架', type: PolicyType::Button)]
    public function productUp(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('下架', type: PolicyType::Button)]
    public function productDown(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('审核', type: PolicyType::Button)]
    public function productAudit(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('修改浏览量', type: PolicyType::Button)]
    public function productUpgradeViews(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
