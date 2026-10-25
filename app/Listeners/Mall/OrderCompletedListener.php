<?php

namespace App\Listeners\Mall;

use App\Events\Mall\OrderCompleted;
use App\Notifications\Mall\OrderCompletedNotification;
use Throwable;

class OrderCompletedListener
{
    public function handle(OrderCompleted $event): void
    {
        try {
            $event->order->user->notify(new OrderCompletedNotification($event->order));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
