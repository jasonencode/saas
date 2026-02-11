<?php

namespace App\Models;

use App\Contracts\Authenticatable;
use App\Events\UserCreatedEvent;
use App\Models\Traits\BelongsToTenant;
use Exception;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

/**
 * 用户模型
 */
class User extends Authenticatable
{
    use BelongsToTenant,
        HasApiTokens,
        SoftDeletes;

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

        self::created(static function (User $user) {
            try {
                $user->profile()->create([
                    'nickname' => '用户:'.substr($user->username, -4),
                ]);
                $user->account()->create();
            } catch (Exception) {
            }
        });
    }

    /**
     * 用户资料
     *
     * @return HasOne
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * 用户账户
     *
     * @return HasOne
     */
    public function account(): HasOne
    {
        return $this->hasOne(UserAccount::class);
    }

    /**
     * user-file 使用
     *
     * @return string
     */
    public function getAvatarAttribute(): string
    {
        return $this->profile?->avatar_url ?? '';
    }

    /**
     * 获取用户名(展示用)
     *
     * @return string|null
     */
    protected function getNameAttribute(): ?string
    {
        return $this->profile?->nickname;
    }
}
