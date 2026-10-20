<?php

namespace App\Listeners\Mall;

use App\Events\Mall\OrderDelivered;
use App\Notifications\Mall\OrderDeliveredNotification;
use Throwable;

class OrderDeliveredListener
{
    public function handle(OrderDelivered $event): void
    {
        try {
            $event->order->user->notify(new OrderDeliveredNotification($event->order));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
