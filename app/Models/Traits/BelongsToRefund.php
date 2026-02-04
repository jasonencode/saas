<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Refund;

trait BelongsToRefund
{
    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class)->withTrashed();
    }

    public function setRefundAttribute(Refund $refund): void
    {
        $this->attributes['refund_id'] = $refund->getKey();
    }
}
