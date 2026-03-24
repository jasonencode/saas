<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Support\Facades\Cache;

/**
 * 敏感词模型
 */
#[Unguarded]
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
