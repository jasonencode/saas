<?php

namespace App\Models\Content;

use App\Enums\PlatformType;
use App\Models\Model;
use App\Policies\AppVersionPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 应用版本模型
 */
#[Unguarded]
#[UsePolicy(AppVersionPolicy::class)]
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

