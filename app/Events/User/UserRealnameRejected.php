<?php

namespace App\Events\User;

use App\Models\UserRealname;
use Illuminate\Foundation\Events\Dispatchable;

class UserRealnameRejected
{
    use Dispatchable;

    public function __construct(
        public UserRealname $realname,
        public string $reason,
    ) {}
}
