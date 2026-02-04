<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function skus(): BelongsToMany
    {
        return $this->belongsToMany(Sku::class, 'sku_attribute')
            ->using(SkuAttribute::class)
            ->withPivot('attribute_value_id')
            ->withTimestamps();
    }

    public function goods(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
