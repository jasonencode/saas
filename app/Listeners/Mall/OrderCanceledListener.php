<?php

namespace App\Listeners\Mall;

use App\Events\Mall\OrderCanceled;
use Exception;

class OrderCanceledListener
{
    public function handle(OrderCanceled $event): void
    {
        try {
        } catch (Exception) {
        }
    }
}
