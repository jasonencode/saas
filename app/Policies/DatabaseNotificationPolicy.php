<?php

namespace App\Policies;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
use Illuminate\Notifications\DatabaseNotification;

class DatabaseNotificationPolicy extends Policy
{
    protected string $modelName = '数据库通知';

    protected int $platform = 1;

    #[PolicyName('查看列表', '')]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('删除', '')]
    public function delete(Authenticatable $user, DatabaseNotification $notification): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('批量删除')]
    public function deleteAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
