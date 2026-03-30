<?php

namespace App\Policies;

use App\Contracts\Authenticatable;
use App\Contracts\PolicyName;

class ExpressPolicy extends MallPolicy
{
    protected string $modelName = '物流公司';

    protected string $groupName = '商城中心';

    #[PolicyName('列表', '')]
    public function viewAny(Authenticatable $user): bool
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
}