<?php

namespace App\Models;

use App\Models\Traits\BelongsToOrder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 订单明细模型
 */
class OrderItem extends Model
{
    use BelongsToOrder;

    public $timestamps = false;

    /**
     * 关联商品
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)
            ->withTrashed();
    }

    /**
     * 关联规格
     *
     * @return BelongsTo
     */
    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    /**
     * 小计金额
     *
     * @return float
     */
    public function getSubTotalAttribute(): float
    {
        return bcmul($this->qty, $this->price, 2);
    }

    /**
     * 关联退款明细
     *
     * @return HasOne
     */
    public function refundItem(): HasOne
    {
        return $this->hasOne(RefundItem::class);
    }
}
