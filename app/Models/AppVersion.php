<?php

namespace App\Models;

use App\Enums\PlatformType;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 应用版本模型
 */
class AppVersion extends Model
{
    use Cachable,
        SoftDeletes;

    protected $casts = [
        'platform' => PlatformType::class,
        'description' => 'json',
        'force' => 'boolean',
        'publish_at' => 'datetime',
    ];
}

