<?php

namespace App\Models\Finance;

use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
class VoucherLog extends Model
{
    const null UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'meta' => 'json',
        ];
    }

    /**
     * 关联凭据
     *
     * @return BelongsTo<Voucher>
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}
