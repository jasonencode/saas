<?php

namespace App\Models\Mall;

use App\Contracts\Orderable;
use App\Enums\Mall\ProductStatus;
use App\Models\Model;
use App\Models\Traits\HasCovers;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
class Sku extends Model implements Orderable
{
    use HasCovers,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'origin_price' => 'decimal:2',
            'price' => 'decimal:2',
            'stock' => 'integer',
            'sale' => 'integer',
            'sort' => 'integer',
        ];
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
     * {@inheritDoc}
     */
    public function getTenantId(): int
    {
        return (int) $this->product->tenant_id;
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderableName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function getOrderablePrice(): string
    {
        return number_format((float) $this->price, 2, '.', '');
    }

    /**
     * {@inheritDoc}
     */
    public function checkOrderable(int $qty = 1): ?string
    {
        if ($this->product->status !== ProductStatus::Up) {
            return sprintf('商品[%s]已下架或不可购买', $this->product->name);
        }

        if ($this->stock < $qty) {
            return sprintf('商品[%s]规格[%s]库存不足', $this->product->name, $this->name);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function deductStock(int $qty): void
    {
        $this->decrement('stock', $qty);
    }

    /**
     * {@inheritDoc}
     */
    public function restoreStock(int $qty): void
    {
        $this->increment('stock', $qty);
    }
}
