<?php

namespace App\Models;

use App\Services\BlackListService;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;

class BlackList extends Model
{
    use Cachable;

    const UPDATED_AT = null;

    protected static function boot(): void
    {
        parent::boot();

        self::saved(function () {
            resolve(BlackListService::class)->cleanCache();
        });

        self::deleted(function () {
            resolve(BlackListService::class)->cleanCache();
        });
    }
}
