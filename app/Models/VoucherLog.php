<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 结算凭据日志模型
 *
 * @module 结算
 */
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
