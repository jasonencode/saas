<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Policies\AttributePolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 商品属性模型
 */
#[Unguarded]
#[UsePolicy(AttributePolicy::class)]
class Attribute extends Model
{
    /**
     * 属性值关联
     *
     * @return HasMany
     */
    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }

    /**
     * SKU关联
     *
     * @return BelongsToMany
     */
    public function skus(): BelongsToMany
    {
        return $this->belongsToMany(Sku::class, 'sku_attribute')
            ->using(SkuAttribute::class)
            ->withPivot('attribute_value_id')
            ->withTimestamps();
    }

    /**
     * 商品关联
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
