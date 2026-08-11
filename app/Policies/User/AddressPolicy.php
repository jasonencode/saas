<?php

namespace App\Policies\User;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
use App\Enums\System\PolicyType;
use App\Models\User\Address;

class AddressPolicy extends Policy
{
    protected string $modelName = '收货地址';

    protected string $groupName = '用户管理';

    protected int $platform = 1;

    #[PolicyName('列表', type: PolicyType::Page)]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('恢复', type: PolicyType::Button)]
    public function restore(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('修改排序', type: PolicyType::Button)]
    public function upgradeSort(Authenticatable $user, Address $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
