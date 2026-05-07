<?php

namespace App\Models\System;

use App\Models\Model;
use App\Policies\System\BlackListPolicy;
use App\Services\System\BlackListService;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;

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
