<?php

namespace App\Models;

use App\Models\Model;
use App\Models\Traits\HasCovers;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sku extends Model
{
    use HasCovers;

    protected $table = 'mall_skus';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)
            ->withTrashed();
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'mall_sku_attribute')
            ->using(SkuAttribute::class)
            ->withPivot('attribute_value_id')
            ->withTimestamps();
    }
}
