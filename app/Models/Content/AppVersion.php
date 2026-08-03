<?php

namespace App\Models\Content;

use App\Enums\Content\PlatformType;
use App\Models\Model;
use App\Policies\Content\AppVersionPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(AppVersionPolicy::class)]
class AppVersion extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'platform' => PlatformType::class,
            'description' => 'json',
            'force' => 'boolean',
            'publish_at' => 'datetime',
        ];
    }
}
