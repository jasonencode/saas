<?php

namespace App\Policies;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
use App\Models\System;

class SystemPolicy extends Policy
{
    protected string $modelName = '系统设置';

    protected string $groupName = '系统管理';

    protected int $platform = 1;

    #[PolicyName('列表', '')]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('创建', '')]
    public function create(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('编辑', '')]
    public function update(Authenticatable $user, System $system): bool
    {
        if (in_array($system->getKey(), [1, 2, 3], true)) {
            return false;
        }
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('删除', '')]
    public function delete(Authenticatable $user, System $system): bool
    {
        if (in_array($system->getKey(), [1, 2, 3], true)) {
            return false;
        }
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
