<?php

namespace App\Events\User;

use App\Enums\User\IdentityChannel;
use App\Models\User\Identity;
use App\Models\User\User;
use Illuminate\Foundation\Events\Dispatchable;

class IdentityChanged
{
    use Dispatchable;

    public function __construct(
        public User $user,
        public ?Identity $before,
        public ?Identity $after,
        public IdentityChannel $channel = IdentityChannel::Auto,
    ) {
    }
}
