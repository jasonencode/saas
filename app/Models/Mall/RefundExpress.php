<?php

namespace App\Models\Mall;

use App\Enums\Mall\RefundExpressStatus;
use App\Models\Model;
use App\Models\Traits\BelongsToRefund;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
class RefundExpress extends Model
{
    use BelongsToRefund;

    protected function casts(): array
    {
        return [
            'status' => RefundExpressStatus::class,
            'shipped_at' => 'datetime',
            'received_at' => 'datetime',
            'checked_at' => 'datetime',
        ];
    }

    /**
     * 关联物流公司
     *
     * @return BelongsTo<Express>
     */
    public function express(): BelongsTo
    {
        return $this->belongsTo(Express::class)
            ->withTrashed();
    }
}
