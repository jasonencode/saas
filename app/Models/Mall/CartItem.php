<?php

namespace App\Models\Mall;

use App\Enums\Mall\ProductStatus;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
class CartItem extends Model
{
    protected function casts(): array
    {
        return [
            'price_at_add' => 'decimal:2',
        ];
    }

    /**
     * 关联购物车
     *
     * @return BelongsTo<Cart>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * 关联 SKU
     *
     * @return BelongsTo<Sku>
     */
    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
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

    /**
     * 小计金额（实时口径，与结算预览/实单计费一致）
     *
     * 供 DeliveryService 等通用计费逻辑通过 getAmount() 统一取额，
     * 避免 method_exists 探测导致预览与实单运费口径不一致。
     *
     * @return string 小计金额
     */
    public function getAmount(): string
    {
        $price = $this->sku?->getOrderablePrice() ?? '0.00';

        return bcmul((string) $this->qty, (string) $price, 2);
    }

    /**
     * 小计金额
     *
     * @return string 小计金额
     */
    public function getSubTotalAttribute(): string
    {
        return bcmul((string) $this->qty, (string) $this->price_at_add, 2);
    }

    /**
     * 检查商品是否可购买
     *
     * @return bool 是否可购买
     */
    public function isAvailable(): bool
    {
        return $this->product &&
            $this->product->status === ProductStatus::Up &&
            $this->sku &&
            $this->sku->stock >= $this->qty;
    }
}
