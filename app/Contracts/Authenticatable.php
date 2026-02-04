<?php

namespace App\Contracts;

use App\Models\AdminRolePermission;
use App\Models\LoginRecord;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notifiable;

abstract class Authenticatable extends User
{
    use Notifiable;

    public function hasPermission(string $policy, string $method): bool
    {
        return AdminRolePermission::whereIn('role_id', $this->roles->pluck('id')->toArray())
            ->where('policy', $policy)
            ->where('method', $method)
            ->exists();
    }

    public function records(): MorphMany
    {
        return $this->morphMany(LoginRecord::class, 'user');
    }

    abstract protected function getNameAttribute(): ?string;
}
