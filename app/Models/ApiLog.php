<?php

namespace App\Models;

use App\Enums\HttpMethod;
use App\Models\Traits\MorphToUser;
use App\Policies\ApiLogPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;

/**
 * API日志模型
 */
#[Unguarded]
#[UsePolicy(ApiLogPolicy::class)]
class ApiLog extends Model
{
    use MorphToUser,
        Prunable;

    const null UPDATED_AT = null;

    protected $casts = [
        'method' => HttpMethod::class,
    ];

    /**
     * 获取可修剪的模型查询
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(180));
    }
}
