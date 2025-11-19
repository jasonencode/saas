<?php

namespace App\Policies;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
use App\Models\Module;

class ModulePolicy extends Policy
{
    protected string $modelName = '模块管理';

    protected int $platform = 1;

    #[PolicyName('列表', '')]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('安装', '')]
    public function installModule(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('启用', '')]
    public function enableModule(Authenticatable $user, Module $module): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('禁用', '')]
    public function disableModule(Authenticatable $user, Module $module): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('卸载', '')]
    public function uninstallModule(Authenticatable $user, Module $module): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
