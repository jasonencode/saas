<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\BelongsToOrder;
use App\Policies\CartItemPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 购物车商品项模型
 */
#[Unguarded]
#[UsePolicy(CartItemPolicy::class)]
class CartItem extends Model
{
    use BelongsToOrder;

    protected $casts = [
        'price_at_add' => 'decimal:2',
        'selected' => 'boolean',
    ];

    /**
     * 关联购物车
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * 关联 SKU
     */
    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    /**
     * 关联商品
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)
            ->withTrashed();
    }

    /**
     * 小计金额
     */
    public function getSubTotalAttribute(): float
    {
        return (float) bcmul($this->qty, $this->price_at_add, 2);
    }

    /**
     * 检查商品是否可购买
     */
    public function isAvailable(): bool
    {
        return $this->product &&
            $this->product->status &&
            $this->sku &&
            $this->sku->stock >= $this->qty;
    }
}
