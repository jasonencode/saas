<?php

namespace App\Models\Traits;

use App\Models\System\EventLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * 事件日志关联特征
 *
 * 业务对象通过该特征读取日志中心中挂在自己名下的事件（subject）。
 */
trait HasEventLog
{
    /**
     * 事件日志
     *
     * @return MorphMany<EventLog>
     */
    public function eventLogs(): MorphMany
    {
        return $this->morphMany(EventLog::class, 'subject');
    }
}
