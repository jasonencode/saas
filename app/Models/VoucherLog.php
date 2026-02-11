<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherLog extends Model
{
    const null UPDATED_AT = null;

    protected $table = 'settlement_voucher_logs';

    protected $casts = [
        'meta' => 'json',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}
