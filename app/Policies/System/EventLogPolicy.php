<?php

namespace App\Policies\System;

use App\Contracts\Attributes\PolicyName;
use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Enums\System\PolicyType;
use App\Models\System\EventLog;

/**
 * 日志中心权限
 *
 * 日志由 EventLogService 写入，后台只读展示，因此只提供列表与详情权限。
 */
class EventLogPolicy extends Policy
{
    protected string $modelName = '日志审计';

    protected string $groupName = '系统管理';

    #[PolicyName('列表', type: PolicyType::Page)]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('详情', type: PolicyType::Page)]
    public function view(Authenticatable $user, EventLog $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
