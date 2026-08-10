<?php

namespace App\Models\Mall;

use App\Enums\Content\TagType;
use App\Models\Content\Tag;
use App\Policies\Mall\ProductTagPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[UsePolicy(ProductTagPolicy::class)]
class ProductTag extends Tag
{
    /**
     * 移除商品标签全局 scope
     *
     * @return Builder 不带商品标签 scope 的查询构造器
     */
    public static function withoutProductScope(): Builder
    {
        return static::withoutGlobalScope('product');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (self $model) {
            $model->type = TagType::Product;
        });

        static::addGlobalScope('product', static function (Builder $query) {
            $query->where('type', TagType::Product);
        });
    }

    /**
     * 关联商品
     *
     * @return BelongsToMany<Product>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_tag', 'tag_id', 'product_id');
    }
}
