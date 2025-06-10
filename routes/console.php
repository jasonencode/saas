<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:prune-batches')
    ->daily()
    ->onOneServer()
    ->monitorName('清理批处理队列数据');
Schedule::command('sanctum:prune-expired --hours=24')
    ->daily()
    ->onOneServer()
    ->monitorName('清理过期令牌数据');
Schedule::command('activitylog:clean')
    ->daily()
    ->onOneServer()
    ->monitorName('清理过期日志数据');
