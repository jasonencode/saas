<?php

namespace App\Models\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 租户关联模型特征
 */
trait BelongsToTenant
{
    /**
     * 关联租户
     *
     * @return BelongsTo
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * 租户作用域
     *
     * @param  Builder  $query
     * @param  Tenant|int|null  $tenant
     * @return void
     */
    #[Scope]
    protected function ofTenant(Builder $query, Tenant|int|null $tenant = null): void
    {
        if (is_int($tenant)) {
            $query->where('tenant_id', $tenant);
        } elseif ($tenant instanceof Tenant) {
            $query->whereBelongsTo($tenant);
        }
    }
}
