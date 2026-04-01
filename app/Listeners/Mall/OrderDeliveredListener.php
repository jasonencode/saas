<?php

namespace App\Listeners\Mall;

use App\Events\Mall\OrderDelivered;
use Exception;

class OrderDeliveredListener
{
    public function handle(OrderDelivered $event): void
    {
        try {
        } catch (Exception) {
        }
    }
}
