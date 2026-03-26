<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Jobs\AutoCloseOrder;
use App\Models\StoreConfigure;
use App\Models\Tenant;
use Carbon\Carbon;
use Exception;

class OrderCreatedListener
{
    /**
     * 处理订单创建事件
     */
    public function handle(OrderCreated $event): void
    {
        try {
            $order = $event->order;
            $tenant = $order->tenant;

            // 获取订单超时时间：优先使用租户配置，其次使用全局配置
            $expiredMinutes = $this->getOrderExpiredMinutes($tenant);

            // 设置订单过期时间
            $order->expired_at = Carbon::now()->addMinutes($expiredMinutes);
            $order->save();

            // 调度自动关闭订单任务
            $delay = Carbon::now()->addMinutes($expiredMinutes);
            AutoCloseOrder::dispatch($order)->delay($delay);
        } catch (Exception) {

        }
    }

    /**
     * 获取订单超时时间
     *
     * @param  Tenant  $tenant
     * @return int
     */
    protected function getOrderExpiredMinutes(Tenant $tenant): int
    {
        try {
            $storeConfig = StoreConfigure::where('tenant_id', $tenant->id)->first();

            if ($storeConfig && $storeConfig->order_expired_minutes !== null) {
                return (int) $storeConfig->order_expired_minutes;
            }
        } catch (Exception) {
        }

        return (int) config('custom.mall.order_expired_minutes');
    }
}
