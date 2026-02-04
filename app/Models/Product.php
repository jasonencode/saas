<?php

namespace App\Models;

use App\Contracts\ShouldComment;
use App\Enums\DeductStockType;
use App\Enums\ProductContentType;
use App\Enums\ProductStatus;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasCovers;
use App\Models\Traits\HasSortable;
use App\Models\Traits\ProductScopes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

class Product extends Model implements ShouldComment
{
    use BelongsToTenant,
        HasCovers,
        HasSortable,
        SoftDeletes,
        ProductScopes;

    protected $table = 'mall_products';

    protected $casts = [
        'deduct_stock_type' => DeductStockType::class,
        'status' => ProductStatus::class,
        'can_cart' => 'bool',
        'content_type' => ProductContentType::class,
        'materials' => 'json',
        'ext' => 'json',
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::saved(function (Product $goods) {
            $goods->logs()->create([
                'user_type' => auth()->user()->getMorphClass(),
                'user_id' => auth()->id(),
                'records' => Arr::except($goods->getDirty(), ['updated_at']),
            ]);
        });
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ProductLog::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'mall_product_category')
            ->using(ProductCategory::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class);
    }

    public function getStocksAttribute(): int
    {
        return $this->skus()->sum('stocks');
    }

    public function skus(): HasMany
    {
        return $this->hasMany(Sku::class);
    }

    public function getSalesAttribute(): int
    {
        return $this->skus()->sum('sales');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function getCommentTitleAttribute(): string
    {
        return '[商品]#'.$this->getKey();
    }
}
