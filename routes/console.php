<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:prune-batches')
    ->daily()
    ->onOneServer()
    ->monitorName('修剪批处理队列数据');
Schedule::command('sanctum:prune-expired --hours=24')
    ->daily()
    ->onOneServer()
    ->monitorName('修剪过期令牌数据');
