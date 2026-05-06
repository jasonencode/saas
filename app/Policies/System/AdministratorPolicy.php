<?php

namespace App\Policies\System;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
use App\Models\System\Administrator;
use Illuminate\Support\Facades\Auth;

class AdministratorPolicy extends Policy
{
    protected string $modelName = '管理员';

    protected string $groupName = '系统管理';

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

    #[PolicyName('添加')]
    public function create(Administrator $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('编辑')]
    public function update(Administrator $user, Administrator $administrator): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__) && $administrator->id !== 1;
    }

    #[PolicyName('删除')]
    public function delete(Administrator $user, Administrator $administrator): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__) &&
            $administrator->id !== 1 &&
            $administrator->id !== Auth::id();
    }

    #[PolicyName('批量删除')]
    public function deleteAny(Administrator $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('恢复', '', 1)]
    public function restore(Administrator $user, Administrator $administrator): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__) && $administrator->id !== 1;
    }

    #[PolicyName('批量禁用')]
    public function disableBulk(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量启用')]
    public function enableBulk(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('租户登录')]
    public function stafferLogin(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
