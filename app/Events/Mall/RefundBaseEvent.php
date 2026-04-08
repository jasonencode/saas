<?php

namespace App\Events\Mall;

use App\Models\Mall\Refund;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefundBaseEvent
{
    use Dispatchable,
        SerializesModels;

    public function __construct(protected Refund $refund)
    {
    }
}
