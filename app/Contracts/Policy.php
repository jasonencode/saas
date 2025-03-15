<?php

namespace App\Contracts;

use App\Models\Administrator;
use Illuminate\Foundation\Auth\User;

abstract class Policy
{
    protected string $modelName = '鉴权';

    protected string $groupName = '系统基础';

    protected bool $isTenant = false;

    public function before(User $user): bool|null
    {
        if ($user instanceof Administrator && $user->isAdministrator()) {
            return true;
        }

        return null;
    }

    public function getIsTenant(): bool
    {
        return $this->isTenant;
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
