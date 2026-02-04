<?php

namespace App\Models;

use App\Models\Traits\MorphToUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductLog extends Model
{
    use MorphToUser;

    const null UPDATED_AT = null;

    protected $casts = [
        'records' => 'json',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
