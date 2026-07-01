<?php

namespace App\Models\Mall;

use App\Enums\Content\CategoryType;
use App\Models\Content\Category;
use App\Policies\Mall\ProductCategoryPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Unguarded]
#[UsePolicy(ProductCategoryPolicy::class)]
class ProductCategory extends Category
{
    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (self $model) {
            $model->type = CategoryType::Product;
        });

        static::addGlobalScope('product', static function (Builder $query) {
            $query->where('type', CategoryType::Product);
        });
    }

    /**
     * 移除商品分类全局 scope
     */
    public static function withoutProductScope(): Builder
    {
        return static::withoutGlobalScope('product');
    }

    /**
     * 关联商品
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
