<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentityOrder extends Model
{
    use BelongsToTenant,
        BelongsToUser;

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class);
    }
}
