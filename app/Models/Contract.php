<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
class Contract extends Model
{
    use BelongsToTenant,
        SoftDeletes;

    public function deployer(): BelongsTo
    {
        return $this->belongsTo(ChainAddress::class);
    }
}
