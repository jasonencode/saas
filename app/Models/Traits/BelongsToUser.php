<?php

namespace App\Models\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToUser
{
    public function scopeOfUser(Builder $builder, User $user): void
    {
        $builder->where('user_id', $user->getKey());
    }

    public function scopeOfCurrentUser(Builder $builder): void
    {
        if ($user = Auth::user()) {
            $builder->ofUser($user);
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setUserAttribute(User $user): void
    {
        $this->attributes['user_id'] = $user->getKey();
    }
}
