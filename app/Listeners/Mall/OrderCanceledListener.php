<?php

namespace App\Listeners\Mall;

use App\Events\Mall\OrderCanceled;
use App\Notifications\Mall\OrderCanceledNotification;
use Throwable;

class OrderCanceledListener
{
    public function handle(OrderCanceled $event): void
    {
        try {
            $event->order->user->notify(new OrderCanceledNotification($event->order));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
