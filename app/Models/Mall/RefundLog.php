<?php

namespace App\Models\Mall;

use App\Enums\Mall\RefundLogAction;
use App\Models\Model;
use App\Models\System\Administrator;
use App\Models\Traits\BelongsToRefund;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
class RefundLog extends Model
{
    use BelongsToRefund;

    const null UPDATED_AT = null;

    protected $casts = [
        'action' => RefundLogAction::class,
        'context' => 'json',
    ];

    /**
     * 操作人
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(Administrator::class);
    }
}
