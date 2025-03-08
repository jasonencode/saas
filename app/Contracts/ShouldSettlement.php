<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property User $user
 */
interface ShouldSettlement
{
    public function vouchers(): MorphMany;

    public function user(): BelongsTo;

    public function getSettlementTitleAttribute(): string;
}