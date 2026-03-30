<?php

namespace App\Models;

use App\Policies\SensitivePolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Support\Facades\Cache;

/**
 * 敏感词模型
 */
#[Unguarded]
#[UsePolicy(SensitivePolicy::class)]
class Sensitive extends Model
{
    const null UPDATED_AT = null;

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
