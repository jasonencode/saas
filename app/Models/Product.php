<?php

namespace App\Models;

use App\Contracts\ShouldComment;
use App\Enums\DeductStockType;
use App\Enums\ProductStatus;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasComments;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasSortable;
use App\Models\Traits\ProductScopes;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

/**
 * 商品模型
 */
#[Unguarded]
class Product extends Model implements ShouldComment
{
    use BelongsToTenant,
        HasCovers,
        HasComments,
        HasSortable,
        SoftDeletes,
        ProductScopes;

    protected $casts = [
        'deduct_stock_type' => DeductStockType::class,
        'status' => ProductStatus::class,
        'can_cart' => 'bool',
        'materials' => 'json',
        'ext' => 'json',
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::saved(static function (Product $goods) {
            $goods->logs()->create([
                'user_type' => auth()->user()?->getMorphClass(),
                'user_id' => auth()?->id(),
                'records' => Arr::except($goods->getDirty(), ['updated_at']),
            ]);
        });
    }

    /**
     * 操作日志
     *
     * @return HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ProductLog::class);
    }

    /**
     * 关联品牌
     *
     * @return BelongsTo
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * 关联分类
     *
     * @return BelongsToMany
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_category')
            ->using(ProductCategory::class);
    }

    /**
     * 商品属性
     *
     * @return HasMany
     */
    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class);
    }

    /**
     * 获取总库存
     *
     * @return int
     */
    public function getStocksAttribute(): int
    {
        return $this->skus()->sum('stock');
    }

    /**
     * 商品规格
     *
     * @return HasMany
     */
    public function skus(): HasMany
    {
        return $this->hasMany(Sku::class);
    }

    /**
     * 获取总销量
     *
     * @return int
     */
    public function getSalesAttribute(): int
    {
        return $this->skus()->sum('sale');
    }

    /**
     * 商品评价
     *
     * @return MorphMany
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * 获取评价标题
     *
     * @return string
     */
    public function getCommentTitleAttribute(): string
    {
        return '[商品]#'.$this->getKey();
    }
}
