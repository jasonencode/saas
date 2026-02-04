<?php

namespace App\Models;

use App\Models\Model;
use App\Models\Traits\AutoCreateOrderNo;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\RefundStatus;
use App\Events\RefundInitialized;
use App\Models\Traits\BelongsToOrder;

class Refund extends Model
{
    use AutoCreateOrderNo,
        BelongsToUser,
        BelongsToOrder,
        BelongsToTenant,
        SoftDeletes;

    protected $table = 'mall_refunds';

    protected $casts = [
        'refund_at' => 'datetime',
        'status' => RefundStatus::class,
    ];

    protected $dispatchesEvents = [
        'created' => RefundInitialized::class,
    ];

    public function getRouteKeyName(): string
    {
        return 'no';
    }

    public function items(): HasMany
    {
        return $this->hasMany(RefundItem::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(RefundLog::class);
    }

    public function express(): HasOne
    {
        return $this->hasOne(RefundExpress::class);
    }

    public function refunded(bool $result, ?string $desc = null, ?array $data = null): void
    {
    }
}
