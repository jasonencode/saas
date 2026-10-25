<?php

namespace App\Listeners\Mall;

use App\Events\Mall\OrderSigned;
use App\Notifications\Mall\OrderSignedNotification;
use Throwable;

class OrderSignedListener
{
    public function handle(OrderSigned $event): void
    {
        try {
            $event->order->user->notify(new OrderSignedNotification($event->order));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
