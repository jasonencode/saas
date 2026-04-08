<?php

namespace App\Events\User;

use App\Models\User\UserRealname;
use Illuminate\Foundation\Events\Dispatchable;

class UserRealnameApproved
{
    use Dispatchable;

    public function __construct(public UserRealname $realname) {}
}
