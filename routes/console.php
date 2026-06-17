<?php

use Illuminate\Support\Facades\Schedule;

// 删除过期的队列批次
Schedule::command('queue:prune-batches')
    ->daily()
    ->onOneServer();
// 每日自动修剪 Sanctum 过期的令牌
Schedule::command('sanctum:prune-expired --hours=24')
    ->daily()
    ->onOneServer();
// 每日自动修剪模型数据
Schedule::command('model:prune')->weekly()
    ->onOneServer();

// 商城订单自动完成扫描
Schedule::command('app:mall:order-auto-complete')
    ->daily()
    ->onOneServer();

// 用户身份过期自动清理
Schedule::command('app:user:identity-expire')
    ->daily()
    ->onOneServer();
