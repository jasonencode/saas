<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property User $user
 */
interface ShouldSettlement
{
    public function user(): BelongsTo;
}
