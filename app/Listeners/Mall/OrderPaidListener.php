<?php

namespace App\Listeners\Mall;

use App\Events\Mall\OrderPaid;
use Exception;

class OrderPaidListener
{
    public function handle(OrderPaid $event): void
    {
        try {
        } catch (Exception) {
        }
    }
}
