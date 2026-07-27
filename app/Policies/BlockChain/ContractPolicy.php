<?php

namespace App\Policies\BlockChain;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
use App\Enums\System\PolicyType;
use App\Models\BlockChain\Contract;

class ContractPolicy extends Policy
{
    protected string $modelName = '智能合约';

    protected string $groupName = '区块链管理';

    #[PolicyName('列表', type: PolicyType::Page)]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('详情', type: PolicyType::Page)]
    public function view(Authenticatable $user, Contract $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('创建', type: PolicyType::Page)]
    public function create(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('编辑', type: PolicyType::Page)]
    public function update(Authenticatable $user, Contract $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('删除', type: PolicyType::Button)]
    public function delete(Authenticatable $user, Contract $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量删除', type: PolicyType::Button)]
    public function deleteAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量禁用', type: PolicyType::Button)]
    public function disableBulk(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量启用', type: PolicyType::Button)]
    public function enableBulk(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('部署合约', type: PolicyType::Button)]
    public function contractDeploy(Authenticatable $user, Contract $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
