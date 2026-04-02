<?php

namespace App\Events;

use App\Models\UserRealname;
use Illuminate\Foundation\Events\Dispatchable;

class UserRealnameApproved
{
    use Dispatchable;

    public function __construct(public UserRealname $realname) {}
}
