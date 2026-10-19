<?php

namespace App\Events\Mall;

use App\Contracts\Authenticatable;
use App\Models\Mall\Refund;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RefundBaseEvent implements ShouldDispatchAfterCommit
{
    use Dispatchable,
        SerializesModels;

    public function __construct(
        public Refund $refund,
        public ?Authenticatable $operator = null,
    ) {}
}
