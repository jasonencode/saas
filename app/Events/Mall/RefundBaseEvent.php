<?php

namespace App\Events\Mall;

use App\Contracts\Authenticatable;
use App\Models\Mall\Refund;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefundBaseEvent
{
    use Dispatchable,
        SerializesModels;

    public function __construct(
        public Refund $refund,
        public ?Authenticatable $operator = null,
    ) {}
}
