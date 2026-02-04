<?php

namespace App\Models;

use App\Enums\PlatformType;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppVersion extends Model
{
    use Cachable,
        SoftDeletes;

    protected $table = 'app_versions';

    protected $casts = [
        'platform' => PlatformType::class,
        'description' => 'json',
        'force' => 'boolean',
        'publish_at' => 'datetime',
    ];
}

