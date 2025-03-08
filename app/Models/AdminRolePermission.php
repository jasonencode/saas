<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminRolePermission extends Model
{
    use Cachable;

    const UPDATED_AT = null;

    public function role(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class);
    }
}