<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * 后台管理员租户关联模型
 *
 * @module 后台
 */
class AdministratorTenant extends Pivot
{
    use BelongsToTenant;

    /**
     * 管理员关联
     *
     * @return BelongsTo
     */
    public function administrator(): BelongsTo
    {
        return $this->belongsTo(Administrator::class);
    }
}
