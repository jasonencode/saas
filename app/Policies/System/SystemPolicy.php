<?php

namespace App\Policies\System;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
use App\Enums\System\PolicyType;
use App\Models\System\System;

class SystemPolicy extends Policy
{
    protected string $modelName = '系统设置';

    protected string $groupName = '系统管理';

    protected int $platform = 1;

    #[PolicyName('列表', type: PolicyType::Page)]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('创建', type: PolicyType::Page)]
    public function create(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('编辑', type: PolicyType::Page)]
    public function update(Authenticatable $user, System $system): bool
    {
        if (in_array($system->getKey(), [1, 2, 3], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('删除', type: PolicyType::Button)]
    public function delete(Authenticatable $user, System $system): bool
    {
        if (in_array($system->getKey(), [1, 2, 3], true)) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
