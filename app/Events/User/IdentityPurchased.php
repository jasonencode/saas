<?php

namespace App\Events\User;

use App\Models\User\IdentityOrder;
use Illuminate\Foundation\Events\Dispatchable;

class IdentityPurchased
{
    use Dispatchable;

    public function __construct(
        public IdentityOrder $order,
    ) {}
}
