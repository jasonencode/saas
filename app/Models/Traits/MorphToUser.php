<?php

namespace App\Models\Traits;

use App\Contracts\Authenticatable;
use Illuminate\Database\Eloquent\Relations\MorphTo;

trait MorphToUser
{
    public function user(): MorphTo
    {
        return $this->morphTo();
    }

    public function setUserAttribute(Authenticatable $user): void
    {
        $this->attributes['user_type'] = $user->getMorphClass();
        $this->attributes['user_id'] = $user->getKey();
    }
}
