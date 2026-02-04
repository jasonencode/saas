<?php

namespace App\Jobs;

use App\Jobs\BaseJob;
use App\Models\Mall\Order;

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
