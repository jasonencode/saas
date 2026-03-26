<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 合约模型
 */
#[Unguarded]
class Contract extends Model
{
    use BelongsToTenant,
        SoftDeletes;

    /**
     * 部署者地址
     */
    public function deployer(): BelongsTo
    {
        return $this->belongsTo(ChainAddress::class);
    }
}
