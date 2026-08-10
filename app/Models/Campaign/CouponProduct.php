<?php

namespace App\Models\Campaign;

use App\Models\Mall\Product;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Unguarded]
#[WithoutIncrementing]
class CouponProduct extends Pivot
{
    /**
     * 关联优惠券
     *
     * @return BelongsTo<Coupon>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * 关联商品
     *
     * @return BelongsTo<Product>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
