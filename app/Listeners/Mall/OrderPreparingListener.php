<?php

namespace App\Listeners\Mall;

use App\Events\Mall\OrderPreparing;
use Exception;

class OrderPreparingListener
{
    public function handle(OrderPreparing $event): void
    {
        try {
        } catch (Exception) {
        }
    }
}
