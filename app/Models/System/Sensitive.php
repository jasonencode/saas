<?php

namespace App\Models\System;

use App\Models\Model;
use App\Policies\Content\SensitivePolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Support\Facades\Cache;

#[Unguarded]
#[UsePolicy(SensitivePolicy::class)]
class Sensitive extends Model
{
    const UPDATED_AT = null;

    protected static function boot(): void
    {
        parent::boot();

        self::saved(static function () {
            Cache::delete('sensitive_words_tree');
        });

        self::deleted(static function () {
            Cache::delete('sensitive_words_tree');
        });
    }
}
