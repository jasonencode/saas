<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\HasCovers;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
class Sku extends Model
{
    use HasCovers,
        SoftDeletes;

    protected $casts = [
        'origin_price' => 'decimal:2',
        'price' => 'decimal:2',
        'stock' => 'integer',
        'sale' => 'integer',
        'sort' => 'integer',
    ];

    /**
     * 关联商品
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
