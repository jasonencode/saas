<?php

namespace App\Policies\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;

class OrderLogPolicy extends Policy
{
    protected string $modelName = '订单日志';

    protected string $groupName = '商城管理';

    #[PolicyName('列表', '')]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
