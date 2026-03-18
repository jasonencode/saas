<?php

use Illuminate\Support\Facades\Schedule;

# 删除过期的队列批次
Schedule::command('queue:prune-batches')
    ->daily()
    ->onOneServer();
# 每日自动修剪 Sanctum 过期的令牌
Schedule::command('sanctum:prune-expired --hours=24')
    ->daily()
    ->onOneServer();
# 每日自动修剪模型数据
Schedule::command('model:prune')->daily()
    ->onOneServer();
