<?php

namespace App\Models;

use App\Enums\PlatformType;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 应用版本模型
 */
class AppVersion extends Model
{
    use SoftDeletes;

    protected $casts = [
        'platform' => PlatformType::class,
        'description' => 'json',
        'force' => 'boolean',
        'publish_at' => 'datetime',
    ];
}

