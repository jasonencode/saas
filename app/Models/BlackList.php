<?php

namespace App\Models;

use App\Services\BlackListService;

/**
 * IP黑名单模型
 */
class BlackList extends Model
{
    const null UPDATED_AT = null;

    protected static function boot(): void
    {
        parent::boot();

        self::saved(static function () {
            resolve(BlackListService::class)->cleanCache();
        });

        self::deleted(static function () {
            resolve(BlackListService::class)->cleanCache();
        });
    }
}
