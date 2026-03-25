<?php

namespace App\Models;

use App\Models\Traits\HasCovers;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * 商品规格模型
 */
#[Unguarded]
class Sku extends Model
{
    use HasCovers;

    protected $casts = [
        'origin_price' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    /**
     * 关联商品
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 关联属性
     */
    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'sku_attribute')
            ->using(SkuAttribute::class)
            ->withPivot('attribute_value_id')
            ->withTimestamps();
    }

    /**
     * 获取规格名称（如：颜色:红色 / 尺码:L）
     */
    public function getNameAttribute(): string
    {
        return $this->attributes->map(function ($attr) {
            $value = AttributeValue::find($attr->pivot->attribute_value_id);

            return $attr->name.':'.($value?->value ?? '-');
        })->implode(' / ');
    }
}
