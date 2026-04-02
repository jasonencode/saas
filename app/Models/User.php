<?php

namespace App\Models;

use App\Contracts\Authenticatable;
use App\Events\User\UserCreatedEvent;
use App\Models\Traits\BelongsToTenant;
use App\Policies\UserPolicy;
use Exception;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

/**
 * 用户模型
 */
#[Hidden(['password', 'remember_token'])]
#[Unguarded]
#[UsePolicy(UserPolicy::class)]
class User extends Authenticatable
{
    use BelongsToTenant,
        HasApiTokens,
        SoftDeletes;

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
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * 用户账户
     */
    public function account(): HasOne
    {
        return $this->hasOne(UserAccount::class);
    }

    /**
     * user-file 使用
     */
    public function getAvatarAttribute(): string
    {
        return $this->profile?->avatar_url ?? '';
    }

    /**
     * 获取用户名(展示用)
     */
    protected function getNameAttribute(): ?string
    {
        return $this->profile?->nickname;
    }

    /**
     * 推荐关系
     */
    public function relation(): HasOne
    {
        return $this->hasOne(UserRelation::class)
            ->withDefault();
    }

    /**
     * 用户身份
     */
    public function identities(): BelongsToMany
    {
        return $this->belongsToMany(Identity::class, 'user_identity')
            ->withPivot(['start_at', 'end_at', 'serial'])
            ->using(UserIdentity::class)
            ->withTimestamps();
    }

    /**
     * 用户地址
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * 用户实名认证
     */
    public function realname(): HasOne
    {
        return $this->hasOne(UserRealname::class);
    }

    /**
     * 用户订单
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * 用户评论
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * 用户优惠券
     */
    public function coupons(): HasMany
    {
        return $this->hasMany(CouponUser::class);
    }

    /**
     * 身份变更日志
     */
    public function identityLogs(): HasMany
    {
        return $this->hasMany(IdentityLog::class);
    }
}
