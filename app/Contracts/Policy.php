<?php

namespace App\Contracts;

use App\Models\Administrator;
use Illuminate\Foundation\Auth\User;

abstract class Policy
{
    protected string $modelName = '鉴权' {
        get {
            return $this->modelName;
        }
    }

    protected string $groupName = '系统权限' {
        get {
            return $this->groupName;
        }
    }

    protected int $platform = 3 {
        get {
            return $this->platform;
        }
    }

    public function before(User $user): bool|null
    {
        if ($user instanceof Administrator && $user->isAdministrator()) {
            return true;
        }

        return null;
    }
}
