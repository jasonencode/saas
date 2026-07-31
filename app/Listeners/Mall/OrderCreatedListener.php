<?php

namespace App\Listeners\Mall;

use App\Events\Mall\OrderCreated;
use App\Jobs\Mall\AutoCloseOrder;
use App\Models\Mall\StoreConfigure;
use App\Models\System\Tenant;
use Carbon\Carbon;
use Throwable;

class OrderCreatedListener
{
    public function handle(OrderCreated $event): void
    {
        try {
            $order = $event->order;
            $expiredMinutes = $this->getOrderExpiredMinutes($order->tenant);

            $order->expired_at = Carbon::now()->addMinutes($expiredMinutes);
            $order->save();

            AutoCloseOrder::dispatch($order)->delay($order->expired_at);
        } catch (Throwable $e) {
            report($e);
        }
    }

    protected function getOrderExpiredMinutes(Tenant $tenant): int
    {
        $storeConfig = StoreConfigure::where('tenant_id', $tenant->id)
            ->first();

        if ($storeConfig?->order_expired_minutes !== null) {
            return (int) $storeConfig->order_expired_minutes;
        }

        return (int) config('custom.mall.order_expired_minutes');
    }
}
