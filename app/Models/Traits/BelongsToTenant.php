<?php

namespace App\Models\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    #[Scope]
    protected function ofTenant(Builder $query, Tenant|int|null $tenant = null): void
    {
        if (is_int($tenant)) {
            $query->where('tenant_id', $tenant);
        } elseif ($tenant) {
            $query->whereBelongsTo($tenant);
        }
    }
}
