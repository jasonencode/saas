<?php

namespace App\Policies\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\PolicyName;
use App\Policies\System\MallPolicy;

class OrderLogPolicy extends MallPolicy
{
    protected string $modelName = '订单日志';

    protected string $groupName = '商城中心';

    #[PolicyName('列表', '')]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
