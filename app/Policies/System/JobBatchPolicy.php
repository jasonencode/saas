<?php

namespace App\Policies\System;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
use App\Enums\System\PolicyType;

class JobBatchPolicy extends Policy
{
    protected string $modelName = '批处理任务';

    protected string $groupName = '系统管理';

    protected int $platform = 1;

    #[PolicyName('列表', type: PolicyType::Page)]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('取消任务', type: PolicyType::Button)]
    public function cancelJobBatch(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('重试失败任务', type: PolicyType::Button)]
    public function retryJobBatch(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
