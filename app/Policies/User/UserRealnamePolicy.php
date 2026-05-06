<?php

namespace App\Policies\User;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
use App\Enums\User\RealnameStatus;
use App\Models\User\UserRealname;

class UserRealnamePolicy extends Policy
{
    protected string $modelName = '实名认证';

    protected string $groupName = '用户中心';

    #[PolicyName('列表', '')]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('详情', '')]
    public function view(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('审核通过')]
    public function approveRealname(Authenticatable $user, UserRealname $record): bool
    {
        if ($record->status !== RealnameStatus::Pending) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('拒绝')]
    public function rejectRealname(Authenticatable $user, UserRealname $record): bool
    {
        if ($record->status !== RealnameStatus::Pending) {
            return false;
        }

        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
