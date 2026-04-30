<?php

namespace App\Policies\User;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;

class AddressPolicy extends Policy
{
    protected string $modelName = '鏀惰揣鍦板潃';

    protected string $groupName = '鐢ㄦ埛涓績';

    protected int $platform = 1;

    #[PolicyName('鍒楄〃', '')]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('鎭㈠')]
    public function restore(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
