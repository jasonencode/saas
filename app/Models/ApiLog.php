<?php

namespace App\Models;

use App\Enums\HttpMethod;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * API日志模型
 */
#[Unguarded]
class ApiLog extends Model
{
    use Prunable;

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

    /**
     * 获取可修剪的模型查询
     *
     * @return Builder
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(180));
    }
}
