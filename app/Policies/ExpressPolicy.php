<?php

namespace App\Policies;

use App\Contracts\Authenticatable;
use App\Contracts\PolicyName;

class ExpressPolicy extends MallPolicy
{
    protected string $modelName = '快递管理';

    #[PolicyName('列表', '')]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}