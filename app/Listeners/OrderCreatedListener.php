<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Jobs\AutoCloseOrder;
use Carbon\Carbon;

class OrderCreatedListener
{
    public function handle(OrderCreated $event): void
    {
        $delay = Carbon::now()->addMinutes((int) config('mall.order_expired_minutes'));

        AutoCloseOrder::dispatch($event->order)->delay($delay);
    }
}