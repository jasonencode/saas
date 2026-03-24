<?php

namespace App\Models;

use App\Models\Traits\MorphToUser;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 商品日志模型
 */
#[Unguarded]
class ProductLog extends Model
{
    use MorphToUser;

    const null UPDATED_AT = null;

    protected $casts = [
        'records' => 'json',
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
}
