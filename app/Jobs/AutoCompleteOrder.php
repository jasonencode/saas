<?php

namespace App\Jobs;

use App\Models\Order;

class AutoCompleteOrder extends BaseJob
{
    public function __construct(protected Order $order)
    {
    }

    public function handle(): void
    {
        if ($this->order->canComplete()) {
            $this->order->complete();
        }
    }
}
