<?php

namespace App\Listeners\Mall;

use App\Events\Mall\OrderPaid;
use App\Notifications\Mall\OrderPaidNotification;
use Throwable;

class OrderPaidListener
{
    public function handle(OrderPaid $event): void
    {
        try {
            $event->order->user->notify(new OrderPaidNotification($event->order));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
