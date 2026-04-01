<?php

namespace App\Listeners\Mall;

use App\Events\Mall\OrderSigned;
use Exception;

class OrderSignedListener
{
    public function handle(OrderSigned $event): void
    {
        try {
        } catch (Exception) {
        }
    }
}
