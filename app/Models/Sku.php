<?php

namespace App\Models;

use App\Models\Traits\HasCovers;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sku extends Model
{
    use HasCovers;

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)
            ->withTrashed();
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'sku_attribute')
            ->using(SkuAttribute::class)
            ->withPivot('attribute_value_id')
            ->withTimestamps();
    }
}
