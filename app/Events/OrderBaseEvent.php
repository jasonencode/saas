<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

class OrderBaseEvent
{
    use Dispatchable,
        SerializesModels;

    public function __construct(public Order $order)
    {
    }
}