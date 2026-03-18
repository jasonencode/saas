<?php

namespace App\Models;

use App\Enums\IdentityChannel;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentityLog extends Model
{
    use BelongsToTenant,
        BelongsToUser;

    protected $casts = [
        'source' => 'json',
        'channel' => IdentityChannel::class,
    ];

    public function before(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'before');
    }

    public function after(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'after');
    }
}
