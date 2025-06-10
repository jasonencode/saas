<?php

namespace App\Policies;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
use App\Models\Administrator;

class AdministratorPolicy extends Policy
{
    protected string $modelName = '管理员管理';

    protected string $groupName = '系统设置';

    #[PolicyName('列表', '管理员列表')]
    public function viewAny(Administrator $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('详情')]
    public function view(Administrator $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('新增')]
    public function create(Administrator $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('编辑')]
    public function update(Administrator $user, Administrator $administrator): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__) && $administrator->id != 1;
    }

    #[PolicyName('删除')]
    public function delete(Administrator $user, Administrator $administrator): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__) &&
            $administrator->id != 1 &&
            $administrator->id != auth()->id();
    }

    #[PolicyName('批量删除')]
    public function deleteAny(Administrator $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('恢复', '', 1)]
    public function restore(Administrator $user, Administrator $administrator): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__) && $administrator->id != 1;
    }

    #[PolicyName('批量恢复', '', 1)]
    public function restoreAny(Administrator $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('永久删除', '', 1)]
    public function forceDelete(Administrator $user, Administrator $administrator): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__) && $administrator->id != 1;
    }

    #[PolicyName('批量永久删除', '', 1)]
    public function forceDeleteAny(Administrator $user): bool
    {
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

    #[PolicyName('租户登录')]
    public function tenantStafferLogin(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
