<?php

namespace App\Listeners;

use App\Models\Tenant;
use Illuminate\Auth\Events\Login;

class ClearTenantTokens
{
    public function handle(Login $event): void
    {
        if (!$event->user instanceof Tenant) {
            return;
        }

        $event->user->tokens()->delete();
    }
}
