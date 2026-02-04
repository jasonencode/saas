<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductCategory extends Pivot
{
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): void
    {
        $this->belongsTo(Category::class);
    }
}
