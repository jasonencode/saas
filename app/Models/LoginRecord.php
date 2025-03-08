<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphTo;

class LoginRecord extends Model
{
    const UPDATED_AT = null;

    public function user(): MorphTo
    {
        return $this->morphTo();
    }
}
