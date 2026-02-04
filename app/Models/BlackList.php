<?php

namespace App\Models;

use App\Services\BlackListService;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;

class BlackList extends Model
{
    use Cachable;

    const null UPDATED_AT = null;

    protected static function boot(): void
    {
        parent::boot();

        self::saved(static function() {
            resolve(BlackListService::class)->cleanCache();
        });

        self::deleted(static function() {
            resolve(BlackListService::class)->cleanCache();
        });
    }
}
