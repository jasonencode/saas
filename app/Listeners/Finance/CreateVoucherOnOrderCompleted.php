<?php

namespace App\Listeners\Finance;

use App\Enums\Finance\PlanTrigger;
use App\Events\Mall\OrderCompleted;
use App\Models\Finance\Plan;
use App\Services\Finance\VoucherService;
use Illuminate\Database\UniqueConstraintViolationException;
use Throwable;

class CreateVoucherOnOrderCompleted
{
    /**
     * 订单完成时按结算计划自动创建凭据
     *
     * 取订单所属租户下「触发方式 = 订单完成」且启用的计划，逐个创建结算凭据并调度执行；
     * 并发重复事件命中凭据唯一约束时静默跳过；租户未配置计划则不产生任何凭据。
     */
    public function handle(OrderCompleted $event): void
    {
        $order = $event->order;

        $plans = Plan::query()
            ->where('tenant_id', $order->tenant_id)
            ->where('trigger', PlanTrigger::OrderCompleted)
            ->ofEnabled()
            ->get();

        foreach ($plans as $plan) {
            try {
                service(VoucherService::class)->create($order, $plan);
            } catch (UniqueConstraintViolationException) {
                // 同一订单在同一计划下已有凭据（事件重复触发），幂等跳过
            } catch (Throwable $e) {
                report($e);
            }
        }
    }
}
