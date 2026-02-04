<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AdministratorTenant extends Pivot
{
    use BelongsToTenant;

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(Administrator::class);
    }
}
