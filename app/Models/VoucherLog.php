<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 结算凭据日志模型
 */
#[Unguarded]
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
