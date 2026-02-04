<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Mall\Order;

class OrderBaseEvent
{
    use Dispatchable,
        SerializesModels;

    public function __construct(public Order $order)
    {
    }
}