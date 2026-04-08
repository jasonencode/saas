<?php

namespace App\Models\Finance;

use App\Models\Model;
use App\Policies\VoucherLogPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 结算凭据日志模型
 */
#[Unguarded]
#[UsePolicy(VoucherLogPolicy::class)]
class VoucherLog extends Model
{
    const null UPDATED_AT = null;

    protected $casts = [
        'meta' => 'json',
    ];

    /**
     * 关联凭据
     *
     * @return BelongsTo
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}
