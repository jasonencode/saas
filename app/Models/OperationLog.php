<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 操作日志
 */
class OperationLog extends Model
{
    const null UPDATED_AT = null;

    protected $casts = [
        'query' => 'json',
        'response' => 'json',
        'log' => 'json',
    ];

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(Administrator::class);
    }
}
