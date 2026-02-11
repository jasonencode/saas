<?php

namespace App\Models;

use App\Enums\HttpMethod;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * API日志模型
 */
class ApiLog extends Model
{
    const null UPDATED_AT = null;

    protected $casts = [
        'method' => HttpMethod::class,
    ];

    /**
     * 用户关联模型
     *
     * @return MorphTo
     */
    public function user(): MorphTo
    {
        return $this->morphTo();
    }
}
