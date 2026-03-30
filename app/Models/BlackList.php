<?php

namespace App\Models;

use App\Policies\BlackListPolicy;
use App\Services\BlackListService;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;

/**
 * IP黑名单模型
 */
#[Unguarded]
#[UsePolicy(BlackListPolicy::class)]
class BlackList extends Model
{
    const null UPDATED_AT = null;

    protected static function boot(): void
    {
        parent::boot();

        self::saved(static function () {
            service(BlackListService::class)->cleanCache();
        });

        self::deleted(static function () {
            service(BlackListService::class)->cleanCache();
        });
    }
}
