<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use BelongsToTenant,
        SoftDeletes;

    public function deployer(): BelongsTo
    {
        return $this->belongsTo(ChainAddress::class);
    }
}
