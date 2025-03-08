<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class AuthenticatedRecord
{
    public function __construct()
    {
    }

    public function handle(Login $event): void
    {
        $event->user->records()->create([
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
