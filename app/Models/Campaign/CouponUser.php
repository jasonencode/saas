<?php

namespace App\Models\Campaign;

use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Unguarded]
class CouponUser extends Pivot
{
    use BelongsToUser;

    protected $casts = [
        'is_used' => 'bool',
        'expired_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * 关联优惠券
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
