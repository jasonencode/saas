<?php

namespace App\Listeners\Mall;

use App\Events\Mall\OrderPreparing;
use App\Notifications\Mall\OrderPreparingNotification;
use Throwable;

class OrderPreparingListener
{
    public function handle(OrderPreparing $event): void
    {
        try {
            $event->order->user->notify(new OrderPreparingNotification($event->order));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
