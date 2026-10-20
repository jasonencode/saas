<?php

namespace App\Listeners\Mall;

use App\Events\Mall\OrderPartiallyShipped;
use App\Notifications\Mall\OrderPartiallyShippedNotification;
use Throwable;

class OrderPartiallyShippedListener
{
    public function handle(OrderPartiallyShipped $event): void
    {
        try {
            $event->order->user->notify(new OrderPartiallyShippedNotification($event->order));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
