<?php

namespace App\Models;

use App\Models\Traits\BelongsToOrder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    use BelongsToOrder;

    public $timestamps = false;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)
            ->withTrashed();
    }

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

    public function refundItem(): HasOne
    {
        return $this->hasOne(RefundItem::class);
    }
}
