<?php

namespace App\Policies\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
use App\Enums\System\PolicyType;
use App\Models\Mall\Region;

class RegionPolicy extends Policy
{
    protected string $modelName = '行政区划';

    protected string $groupName = '商城管理';

    protected int $platform = 1;

    #[PolicyName('列表', type: PolicyType::Page)]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('修改排序', type: PolicyType::Button)]
    public function upgradeSort(Authenticatable $user, Region $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
