<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\BelongsToOrder;
use App\Policies\Mall\OrderItemPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Unguarded]
#[UsePolicy(OrderItemPolicy::class)]
#[WithoutTimestamps]
class OrderItem extends Model
{
    use BelongsToOrder;

    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * 关联商品
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)
            ->withTrashed();
    }

    /**
     * 关联商品规格
     */
    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class, 'product_sku_id')
            ->withTrashed();
    }

    /**
     * 小计金额
     */
    public function getSubTotalAttribute(): float
    {
        return (float) bcmul($this->qty, $this->price, 2);
    }

    /**
     * 关联退款明细
     *
     * @return HasMany<RefundItem>
     */
    public function refundItems(): HasMany
    {
        return $this->hasMany(RefundItem::class);
    }

    /**
     * 关联物流
     */
    public function orderShipping(): BelongsTo
    {
        return $this->belongsTo(OrderShipping::class);
    }
}
