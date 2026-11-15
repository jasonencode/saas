<?php

namespace App\Policies\Campaign;

use App\Contracts\Attributes\PolicyName;
use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Enums\System\PolicyType;
use App\Models\Campaign\RedpackCode;

class RedpackCodePolicy extends Policy
{
    protected string $modelName = '红包码';

    protected string $groupName = '营销中心';

    #[PolicyName('列表', type: PolicyType::Page)]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('详情', type: PolicyType::Page)]
    public function view(Authenticatable $user, RedpackCode $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('编辑', type: PolicyType::Button)]
    public function update(Authenticatable $user, RedpackCode $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    /**
     * 红包码禁止删除：码即资金凭证，删除后审计链断裂，应通过禁用状态收敛
     */
    #[PolicyName('删除', type: PolicyType::Button)]
    public function delete(Authenticatable $user, RedpackCode $record): bool
    {
        return false;
    }

    #[PolicyName('批量删除', type: PolicyType::Button)]
    public function deleteAny(Authenticatable $user): bool
    {
        return false;
    }

    #[PolicyName('永久删除', type: PolicyType::Button)]
    public function forceDelete(Authenticatable $user): bool
    {
        return false;
    }

    #[PolicyName('批量永久删除', type: PolicyType::Button)]
    public function forceDeleteAny(Authenticatable $user): bool
    {
        return false;
    }
}
