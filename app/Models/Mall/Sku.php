<?php

namespace App\Models\Mall;

use App\Contracts\Orderable;
use App\Contracts\Refundable;
use App\Enums\Mall\DeductStockType;
use App\Enums\Mall\ProductStatus;
use App\Models\Model;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasSortable;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
class Sku extends Model implements Orderable, Refundable
{
    use HasCovers,
        HasSortable,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'origin_price' => 'decimal:2',
            'price' => 'decimal:2',
            'weight' => 'decimal:2',
            'volume' => 'decimal:2',
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
     * 获取所属租户 ID
     *
     * @return int 租户 ID
     */
    public function getTenantId(): int
    {
        return (int) $this->product->tenant_id;
    }

    /**
     * 获取可下单名称
     *
     * @return string SKU 名称
     */
    public function getOrderableName(): string
    {
        return sprintf('%s %s', $this->product->name, $this->name);
    }

    /**
     * 获取可下单价格
     *
     * @return string 格式化后的价格（保留两位小数）
     */
    public function getOrderablePrice(): string
    {
        return number_format((float) $this->price, 2, '.', '');
    }

    /**
     * 检查是否可下单
     *
     * @param  int  $qty  购买数量
     *
     * @return string|null 错误信息，可下单时返回 null
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
     * 获取扣减库存方式
     *
     * @return DeductStockType 扣减库存方式
     */
    public function getDeductStockType(): DeductStockType
    {
        return $this->product->deduct_stock_type;
    }

    /**
     * 扣减库存
     *
     * @param  int  $qty  扣减数量
     */
    public function deductStock(int $qty): void
    {
        $this->decrement('stock', $qty);
    }

    /**
     * 恢复库存
     *
     * @param  int  $qty  恢复数量
     */
    public function restoreStock(int $qty): void
    {
        $this->increment('stock', $qty);
    }

    /**
     * 是否需要退回实物
     *
     * @return bool 实体商品需要买家寄回
     */
    public function needsReturn(): bool
    {
        return true;
    }

    /**
     * 退款时恢复库存
     *
     * @param  RefundItem  $refundItem  退款项
     * @param  int  $qty  退款数量
     */
    public function refund(RefundItem $refundItem, int $qty): void
    {
        $this->restoreStock($qty);
    }
}
