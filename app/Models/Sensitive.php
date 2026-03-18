<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Support\Facades\Cache;

/**
 * 敏感词模型
 */
class Sensitive extends Model
{
    use Cachable;

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
