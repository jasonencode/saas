<?php

namespace App\Policies\System;

use App\Contracts\Attributes\PolicyName;
use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Enums\System\PolicyType;
use App\Models\System\ScheduleRunLog;

class ScheduleRunLogPolicy extends Policy
{
    protected string $modelName = '计划任务日志';

    protected string $groupName = '系统管理';

    #[PolicyName('列表', type: PolicyType::Page)]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('详情', type: PolicyType::Page)]
    public function view(Authenticatable $user, ScheduleRunLog $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('清空记录', type: PolicyType::Button)]
    public function cleanScheduleRunLog(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    /**
     * 审计日志禁止删除（策略与动作同时关闭，避免 fail-open 放行）
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
