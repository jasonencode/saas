<?php

namespace App\Models;

use App\Models\Traits\HasCovers;
use App\Policies\SkuPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * 商品规格模型
 */
#[Unguarded]
#[UsePolicy(SkuPolicy::class)]
class Sku extends Model
{
    use HasCovers;

    protected $casts = [
        'origin_price' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    /**
     * 关联商品
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 关联属性
     *
     * @return BelongsToMany
     */
    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'sku_attribute')
            ->using(SkuAttribute::class)
            ->withPivot('attribute_value_id')
            ->withTimestamps();
    }

    /**
     * 获取规格名称（如：颜色:红色|尺码:L）
     *
     * @return string
     */
    public function getNameAttribute(): string
    {
        return $this->attributes()->get()->map(function ($attr) {
            return $attr->name.':'.($attr->pivot->attributeValue->value ?? '-');
        })->implode('|');
    }
}
