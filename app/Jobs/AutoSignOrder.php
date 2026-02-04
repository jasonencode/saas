<?php

namespace App\Jobs;

use App\Models\Order;

class AutoSignOrder extends BaseJob
{
    public function __construct(protected Order $order)
    {
    }

    public function handle(): void
    {
        if ($this->order->canSign()) {
            $this->order->sign();
        }
    }
}
