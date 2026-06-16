<?php

namespace App\Contracts;

use App\Enums\System\PolicyPlatform;
use App\Models\System\Administrator;
use Illuminate\Foundation\Auth\User;

abstract class Policy
{
    protected string $modelName = '鉴权';

    protected string $groupName = '系统权限';

    protected int $platform = PolicyPlatform::Both->value;

    public function before(User $user): ?bool
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

    public function getPlatform(): int
    {
        return $this->platform;
    }
}
