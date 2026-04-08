<?php

namespace App\Models\Campaign;

use App\Enums\Campaign\CouponType;
use App\Enums\Campaign\ExpiredType;
use App\Models\Mall\Order;
use App\Models\Mall\Product;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Models\User;
use App\Policies\CouponPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 优惠券模型
 */
#[Unguarded]
#[UsePolicy(CouponPolicy::class)]
class Coupon extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        SoftDeletes;

    protected $casts = [
        'type' => CouponType::class,
        'expired_type' => ExpiredType::class,
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    /**
     * 检查优惠券是否可以被使用
     */
    public function canBeUsed(): bool
    {
        return $this->isValid() &&
            ($this->usage_limit === null || $this->usage_limit > 0) &&
            ($this->usage_limit_per_user === null || $this->usage_limit_per_user > 0);
    }

    /**
     * 检查优惠券是否有效
     */
    public function isValid(): bool
    {
        return $this->status &&
            ($this->start_at == null || now()->isAfter($this->start_at)) &&
            ($this->end_at == null || now()->isBefore($this->end_at));
    }

    /**
     * 获取过期日期
     */
    public function getExpiredDateAttribute(): mixed
    {
        return $this->expired_type === ExpiredType::Receive ? $this->days : $this->start_at;
    }

    /**
     * 优惠券与商品的多对多关系
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'coupon_product');
    }

    /**
     * 关联用户
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'coupon_user')
            ->withPivot('is_used', 'expired_at', 'used_at')
            ->withTimestamps();
    }

    /**
     * 关联订单
     */
    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'coupon_order')
            ->withPivot('discount_amount')
            ->withTimestamps();
    }
}
