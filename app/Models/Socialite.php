<?php

namespace App\Models;

use App\Enums\SocialiteProvider;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Socialite extends Model
{
    use BelongsToUser,
        BelongsToTenant;

    protected $casts = [
        'provider' => SocialiteProvider::class,
        'expired_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(SocialiteAccount::class);
    }
}
