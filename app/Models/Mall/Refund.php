<?php

namespace App\Models\Mall;

use App\Enums\Mall\RefundReason;
use App\Enums\Mall\RefundStatus;
use App\Enums\Mall\RefundType;
use App\Models\Model;
use App\Models\System\Administrator;
use App\Models\Traits\AutoCreateOrderNo;
use App\Models\Traits\BelongsToOrder;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\RefundScopes;
use App\Policies\Mall\RefundPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(RefundPolicy::class)]
class Refund extends Model
{
    use AutoCreateOrderNo,
        BelongsToOrder,
        BelongsToTenant,
        BelongsToUser,
        RefundScopes,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'goods_amount' => 'decimal:2',
            'freight_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'refund_at' => 'datetime',
            'status' => RefundStatus::class,
            'type' => RefundType::class,
            'reason' => RefundReason::class,
        ];
    }

    /**
     * 获取路由键名
     *
     * @return string 路由键名
     */
    public function getRouteKeyName(): string
    {
        return 'no';
    }

    /**
     * 退款明细
     *
     * @return HasMany<RefundItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(RefundItem::class);
    }

    /**
     * 退款日志
     *
     * @return HasMany<RefundLog>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(RefundLog::class);
    }

    /**
     * 物流信息
     *
     * @return HasOne<RefundExpress>
     */
    public function express(): HasOne
    {
        return $this->hasOne(RefundExpress::class);
    }

    /**
     * 审核人
     *
     * @return BelongsTo<Administrator>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'approved_by');
    }
}
