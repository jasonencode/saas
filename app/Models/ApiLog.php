<?php

namespace App\Models;

use App\Enums\HttpMethod;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApiLog extends Model
{
    const null UPDATED_AT = null;

    protected $casts = [
        'method' => HttpMethod::class,
    ];

    public function user(): MorphTo
    {
        return $this->morphTo();
    }
}
