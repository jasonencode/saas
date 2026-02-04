<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AdministratorRole extends Pivot
{
    public function administrator(): BelongsTo
    {
        return $this->belongsTo(Administrator::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class);
    }
}
