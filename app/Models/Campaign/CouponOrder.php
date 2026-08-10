<?php

namespace App\Models\Campaign;

use App\Models\Mall\Order;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Unguarded]
#[WithoutIncrementing]
class CouponOrder extends Pivot
{
    /**
     * 关联优惠券
     *
     * @return BelongsTo<Coupon>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class)
            ->withTrashed();
    }

    /**
     * 关联订单
     *
     * @return BelongsTo<Order>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class)
            ->withTrashed();
    }
}
