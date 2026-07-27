<?php

namespace App\Policies\System;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
use App\Enums\System\PolicyType;

class ApiLogPolicy extends Policy
{
    protected string $modelName = 'API日志';

    protected string $groupName = '系统管理';

    #[PolicyName('列表', type: PolicyType::Page)]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('详情', type: PolicyType::Page)]
    public function view(Authenticatable $user, ApiLog $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
