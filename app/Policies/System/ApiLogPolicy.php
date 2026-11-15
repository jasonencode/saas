<?php

namespace App\Policies\System;

use App\Contracts\Attributes\PolicyName;
use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Enums\System\PolicyType;
use App\Models\System\ApiLog;

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

    /**
     * API 审计日志禁止删除
     */
    #[PolicyName('删除', type: PolicyType::Button)]
    public function delete(Authenticatable $user): bool
    {
        return false;
    }

    #[PolicyName('批量删除', type: PolicyType::Button)]
    public function deleteAny(Authenticatable $user): bool
    {
        return false;
    }
}
