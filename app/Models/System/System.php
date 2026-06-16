<?php

namespace App\Models\System;

use App\Contracts\Authenticatable;
use App\Policies\System\SystemPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;

#[Unguarded]
#[UsePolicy(SystemPolicy::class)]
class System extends Authenticatable
{
}
