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
Schedule::command('model:prune')
    ->weekly()
    ->onOneServer();

// 商城订单自动完成扫描
Schedule::command('app:mall:order-auto-complete')
    ->daily()
    ->onOneServer();

// 商城订单超时自动签收扫描
Schedule::command('app:mall:order-auto-sign')
    ->hourly()
    ->onOneServer();

// 自提订单超时未核销自动发起退款
Schedule::command('app:mall:pickup-timeout')
    ->daily()
    ->onOneServer();

// 用户身份过期自动清理
Schedule::command('app:user:identity-expire')
    ->daily()
    ->onOneServer();

// 优惠券过期自动清理
Schedule::command('app:campaign:coupon-expire')
    ->daily()
    ->onOneServer();
