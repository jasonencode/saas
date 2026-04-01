<?php

namespace App\Listeners\Mall;

use App\Events\Mall\OrderPartiallyShipped;
use Exception;

class OrderPartiallyShippedListener
{
    public function handle(OrderPartiallyShipped $event): void
    {
        try {
        } catch (Exception) {
        }
    }
}
