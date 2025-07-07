<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;

abstract class Model extends Eloquent
{
    protected $guarded = [];

    /**
     * 日志记录的基础配置
     *
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        if (Filament::isServing()) {
            $name = Filament::getId();
        } else {
            $name = 'api';
        }

        return LogOptions::defaults()
            ->logAll()
            ->logExcept(['created_at', 'updated_at'])
            ->useLogName($name)
            ->logOnlyDirty();
    }

    /**
     * 写入当前租户ID
     *
     * @param  Activity  $activity
     * @return void
     */
    public function tapActivity(Activity $activity): void
    {
        if (Filament::getTenant()) {
            $activity->tenant_id = Filament::getTenant()?->getKey();
        }
    }
}
