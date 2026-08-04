<?php

namespace App\Models\User;

use App\Models\System\Tenant;
use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Unguarded]
class UserTenant extends Pivot
{
    use BelongsToUser;

    /**
     * 关联租户
     *
     * @return BelongsTo<Tenant>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
