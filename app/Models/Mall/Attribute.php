<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Policies\Mall\AttributePolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Unguarded]
#[UsePolicy(AttributePolicy::class)]
class Attribute extends Model
{
    /**
     * 属性值关联
     */
    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }

    /**
     * SKU关联
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
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
