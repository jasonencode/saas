<?php

namespace App\Listeners\Mall;

use App\Events\Mall\OrderCompleted;
use Exception;

class OrderCompletedListener
{
    public function handle(OrderCompleted $event): void
    {
        try {
        } catch (Exception) {
        }
    }
}
