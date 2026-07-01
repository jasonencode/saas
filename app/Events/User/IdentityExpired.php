<?php

namespace App\Events\User;

use App\Models\User\Identity;
use App\Models\User\User;
use Illuminate\Foundation\Events\Dispatchable;

class IdentityExpired
{
    use Dispatchable;

    public function __construct(
        public User $user,
        public Identity $identity,
    ) {}
}
