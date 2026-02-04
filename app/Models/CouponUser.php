<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CouponUser extends Pivot
{
    use BelongsToUser;

    public $incrementing = true;

    protected $casts = [
        'is_used' => 'bool',
        'expired_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
