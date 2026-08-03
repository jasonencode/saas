<?php

namespace App\Models\Campaign;

use App\Enums\Campaign\CouponType;
use App\Enums\Campaign\ExpiredType;
use App\Models\Mall\Order;
use App\Models\Mall\Product;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Models\User\User;
use App\Policies\Campaign\CouponPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(CouponPolicy::class)]
class Coupon extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'expired_type' => ExpiredType::class,
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    /**
     * 检查指定用户是否可领取此优惠券
     *
     * 在上层检查过 canBeUsed() 的前提下，额外检查每人限领
     *
     * @param  User  $user  待检查用户
     *
     * @return bool 是否可领取
     */
    public function canUserUse(User $user): bool
    {
        if (!$this->canBeUsed()) {
            return false;
        }

        // 每人限领已达上限
        if ($this->usage_limit_per_user !== null) {
            $userCount = $this->users()
                ->wherePivot('user_id', $user->getKey())
                ->count();

            if ($userCount >= $this->usage_limit_per_user) {
                return false;
            }
        }

        return true;
    }

    /**
     * 检查优惠券是否可以被使用（全局维度）
     *
     * 检查有效期、状态、总发放量是否已达上限
     *
     * @return bool 是否可被使用
     */
    public function canBeUsed(): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        // 总发放量已达上限
        if ($this->usage_limit !== null && $this->users()->count() >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * 检查优惠券是否有效
     *
     * @return bool 是否有效
     */
    public function isValid(): bool
    {
        return $this->status &&
            ($this->start_at === null || now()->isAfter($this->start_at)) &&
            ($this->end_at === null || now()->isBefore($this->end_at));
    }

    /**
     * 关联用户
     *
     * @return BelongsToMany<User>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'coupon_user')
            ->using(CouponUser::class)
            ->withPivot('is_used', 'expired_at', 'used_at')
            ->withTimestamps();
    }

    /**
     * 已发放数量
     *
     * @return int 已发放数量
     */
    public function getUsageCountAttribute(): int
    {
        return $this->users()->count();
    }

    /**
     * 优惠券与商品的多对多关系
     *
     * @return BelongsToMany<Product>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_product');
    }

    /**
     * 关联订单
     *
     * @return BelongsToMany<Order>
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'coupon_order')
            ->withPivot('discount_amount')
            ->withTimestamps();
    }
}
