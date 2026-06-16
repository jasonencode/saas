<?php

namespace App\Models\Finance;

use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
class VoucherLog extends Model
{
    const UPDATED_AT = null;

    protected $casts = [
        'meta' => 'json',
    ];

    /**
     * 关联凭据
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}
