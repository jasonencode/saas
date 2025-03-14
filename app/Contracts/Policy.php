<?php

namespace App\Contracts;

use App\Models\Administrator;
use Illuminate\Foundation\Auth\User;

abstract class Policy
{
    protected string $modelName = '鉴权';

    protected string $groupName = '系统基础';

    public function before(User $user): bool|null
    {
        if ($user instanceof Administrator && $user->isAdministrator()) {
            return true;
        }

        return null;
    }

    public function getModelName(): string
    {
        return $this->modelName;
    }

    public function getGroupName(): string
    {
        return $this->groupName;
    }
}
