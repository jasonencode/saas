<?php

namespace App\Factories;

use Filament\Facades\Filament;
use Spatie\Activitylog\ActivityLogger;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;

class Loggable
{
    public static function make(): ActivityLogger
    {
        return activity(Filament::getId())
            ->tap(function(ActivityContract $activity) {
                if (Filament::getTenant()) {
                    $activity->tenant_id = Filament::getTenant()->getKey();
                }
            });
    }
}
