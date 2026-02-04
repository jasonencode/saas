<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Refund;

class RefundBaseEvent
{
    use Dispatchable,
        SerializesModels;

    public function __construct(protected Refund $refund)
    {
    }
}
