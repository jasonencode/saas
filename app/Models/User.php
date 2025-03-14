<?php

namespace App\Models;

use App\Contracts\Authenticatable;
use App\Events\UserCreatedEvent;
use App\Models\Traits\BelongsToTenant;
use Exception;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use BelongsToTenant,
        HasApiTokens;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    protected $dispatchesEvents = [
        'created' => UserCreatedEvent::class,
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::created(function(User $user) {
            try {
                $user->info()->create([
                    'nickname' => '用户:'.substr($user->username, -4),
                ]);
            } catch (Exception) {
            }
        });
    }

    public function info(): HasOne
    {
        return $this->hasOne(UserInfo::class);
    }

    protected function getNameAttribute(): ?string
    {
        return $this->info?->nickname;
    }
}
