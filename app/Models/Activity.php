<?php

namespace App\Models;

use App\Enums\ActivityType;
use App\Models\Traits\BelongsToTenant;
use Spatie\Activitylog\Models\Activity as ActivityModel;

class Activity extends ActivityModel
{
    use BelongsToTenant;

    public function getCasts(): array
    {
        return [
            'log_name' => ActivityType::class,
            'properties' => 'collection',
            'is_audit' => 'bool',
        ];
    }
}
