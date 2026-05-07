<?php

namespace App\Models\Mall;

use App\Enums\Content\CategoryType;
use App\Models\Content\Category;
use App\Policies\Mall\ProductCategoryPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Unguarded]
#[UsePolicy(ProductCategoryPolicy::class)]
class ProductCategory extends Category
{
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->type = CategoryType::Product;
        });

        static::addGlobalScope('product', function ($query) {
            $query->where('type', CategoryType::Product);
        });
    }

    /**
     * 关联商品
     *
     * @return HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
