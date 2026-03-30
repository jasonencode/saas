<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use App\Policies\IdentityOrderPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 身份购买订单模型
 */
#[Unguarded]
#[UsePolicy(IdentityOrderPolicy::class)]
class IdentityOrder extends Model
{
    use BelongsToTenant,
        BelongsToUser;

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * 关联身份
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class);
    }
}
