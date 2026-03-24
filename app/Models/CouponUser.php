<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * 优惠券用户关联模型
 */
#[Unguarded]
class CouponUser extends Pivot
{
    use BelongsToUser;

    public $incrementing = true;

    protected $casts = [
        'is_used' => 'bool',
        'expired_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * 关联优惠券
     *
     * @return BelongsTo
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
