<?php

namespace App\Models;

use App\Services\BlackListService;
use Illuminate\Database\Eloquent\Attributes\Unguarded;

/**
 * IP黑名单模型
 */
#[Unguarded]
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
