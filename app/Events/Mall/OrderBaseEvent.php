<?php

namespace App\Events\Mall;

use App\Contracts\Authenticatable;
use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderBaseEvent
{
    use Dispatchable,
        SerializesModels;

    public function __construct(
        public Order $order,
        public ?Authenticatable $operator = null,
    ) {}
}
