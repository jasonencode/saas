<?php

namespace App\Models;

use App\Models\Traits\BelongsToOrder;
use App\Policies\OrderItemPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 订单明细模型
 */
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
     * 关联规格
     */
    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
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
     */
    public function refundItem(): HasOne
    {
        return $this->hasOne(RefundItem::class);
    }

    /**
     * 关联物流
     */
    public function orderExpress(): BelongsTo
    {
        return $this->belongsTo(OrderExpress::class);
    }
}
