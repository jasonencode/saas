<?php

namespace App\Models\User;

use App\Contracts\Authenticatable;
use App\Events\User\UserCreatedEvent;
use App\Models\Campaign\CouponUser;
use App\Models\Content\Comment;
use App\Models\Finance\InvoiceTitle;
use App\Models\Finance\UserAccount;
use App\Models\Mall\Order;
use App\Models\System\Tenant;
use App\Policies\User\UserPolicy;
use Exception;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

#[Hidden(['password', 'remember_token'])]
#[Unguarded]
#[UsePolicy(UserPolicy::class)]
class User extends Authenticatable
{
    use HasApiTokens,
        SoftDeletes;

    protected $dispatchesEvents = [
        'created' => UserCreatedEvent::class,
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        self::created(static function (User $user) {
            try {
                $user->profile()->create([
                    'nickname' => '用户:'.substr($user->username, -4),
                ]);
                $user->account()->create();
            } catch (Exception $e) {
                report($e);
            }
        });
    }

    /**
     * 用户资料
     *
     * @return HasOne<UserProfile>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * 用户账户
     *
     * @return HasOne<UserAccount>
     */
    public function account(): HasOne
    {
        return $this->hasOne(UserAccount::class);
    }

    /**
     * 推荐关系
     *
     * @return HasOne<UserRelation>
     */
    public function relation(): HasOne
    {
        return $this->hasOne(UserRelation::class)
            ->withDefault();
    }

    /**
     * 用户身份
     *
     * @return BelongsToMany<Identity>
     */
    public function identities(): BelongsToMany
    {
        return $this->belongsToMany(Identity::class, 'user_identity')
            ->withPivot(['start_at', 'end_at', 'serial'])
            ->using(UserIdentity::class)
            ->withTimestamps();
    }

    /**
     * 所属租户列表
     *
     * @return BelongsToMany<Tenant>
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'user_tenant')
            ->using(UserTenant::class)
            ->withTimestamps();
    }

    /**
     * 用户地址
     *
     * @return HasMany<Address>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * 用户实名认证
     *
     * @return HasOne<UserRealname>
     */
    public function realname(): HasOne
    {
        return $this->hasOne(UserRealname::class);
    }

    /**
     * 用户发票抬头列表
     *
     * @return HasMany<InvoiceTitle>
     */
    public function invoiceTitles(): HasMany
    {
        return $this->hasMany(InvoiceTitle::class);
    }

    /**
     * 默认发票抬头
     *
     * @return HasOne<InvoiceTitle>
     */
    public function defaultInvoiceTitle(): HasOne
    {
        return $this->hasOne(InvoiceTitle::class)
            ->where('is_default', true);
    }

    /**
     * 用户订单
     *
     * @return HasMany<Order>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * 用户评论
     *
     * @return HasMany<Comment>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * 用户优惠券
     *
     * @return HasMany<CouponUser>
     */
    public function coupons(): HasMany
    {
        return $this->hasMany(CouponUser::class);
    }

    /**
     * 身份变更日志
     *
     * @return HasMany<IdentityLog>
     */
    public function identityLogs(): HasMany
    {
        return $this->hasMany(IdentityLog::class);
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
}
