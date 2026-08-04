<?php

namespace App\Listeners\User;

use App\Events\Mall\OrderPaid;
use App\Services\User\IdentityService;

class GrantIdentityOnOrderPaid
{
    public function __construct(
        private readonly IdentityService $identityService,
    ) {}

    public function handle(OrderPaid $event): void
    {
        $this->identityService->grantOnPaid($event->order);
    }
}
