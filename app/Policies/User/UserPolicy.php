<?php

namespace App\Policies\User;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
use App\Enums\System\PolicyType;
use App\Models\User\User;

class UserPolicy extends Policy
{
    protected string $modelName = '用户管理';

    protected string $groupName = '用户管理';

    #[PolicyName('列表', type: PolicyType::Page)]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('详情', '', 1, type: PolicyType::Page)]
    public function view(Authenticatable $user, User $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('创建', '', 1, type: PolicyType::Page)]
    public function create(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('编辑', '', 1, type: PolicyType::Page)]
    public function update(Authenticatable $user, User $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('删除', '', 1, type: PolicyType::Button)]
    public function delete(Authenticatable $user, User $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量删除', '', 1, type: PolicyType::Button)]
    public function deleteAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('永久删除', '', 1, type: PolicyType::Button)]
    public function forceDelete(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量永久删除', '', 1, type: PolicyType::Button)]
    public function forceDeleteAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('恢复', '', 1, type: PolicyType::Button)]
    public function restore(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量恢复', '', 1, type: PolicyType::Button)]
    public function restoreAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('调整身份', '', 1, type: PolicyType::Button)]
    public function adjustIdentity(Authenticatable $user, User $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
