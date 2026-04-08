<?php

namespace App\Models\Mall;

use App\Enums\Mall\RefundStatus;
use App\Events\Mall\RefundInitialized;
use App\Models\Model;
use App\Models\Traits\AutoCreateOrderNo;
use App\Models\Traits\BelongsToOrder;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\RefundScopes;
use App\Policies\RefundPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 售后订单模型
 */
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

    protected $casts = [
        'total' => 'decimal:2',
        'refund_at' => 'datetime',
        'status' => RefundStatus::class,
    ];

    protected $dispatchesEvents = [
        'created' => RefundInitialized::class,
    ];

    /**
     * 获取路由键名
     */
    public function getRouteKeyName(): string
    {
        return 'no';
    }

    /**
     * 退款明细
     */
    public function items(): HasMany
    {
        return $this->hasMany(RefundItem::class);
    }

    /**
     * 退款日志
     */
    public function logs(): HasMany
    {
        return $this->hasMany(RefundLog::class);
    }

    /**
     * 物流信息
     */
    public function express(): HasOne
    {
        return $this->hasOne(RefundExpress::class);
    }

    /**
     * 退款完成处理
     */
    public function refunded(bool $result, ?string $desc = null, ?array $data = null): void {}
}
