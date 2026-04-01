<?php

namespace App\Listeners\Mall;

use App\Events\Mall\OrderCreated;
use App\Jobs\AutoCloseOrder;
use App\Models\StoreConfigure;
use App\Models\Tenant;
use Carbon\Carbon;
use Exception;

class OrderCreatedListener
{
    public function handle(OrderCreated $event): void
    {
        try {
            $order = $event->order;
            $tenant = $order->tenant;

            $expiredMinutes = $this->getOrderExpiredMinutes($tenant);

            $order->expired_at = Carbon::now()->addMinutes($expiredMinutes);
            $order->save();

            $delay = Carbon::now()->addMinutes($expiredMinutes);
            AutoCloseOrder::dispatch($order)->delay($delay);
        } catch (Exception) {
        }
    }

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
