<?php

namespace App\Models;

use App\Enums\RefundStatus;
use App\Events\RefundInitialized;
use App\Models\Traits\AutoCreateOrderNo;
use App\Models\Traits\BelongsToOrder;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\RefundScopes;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 售后订单模型
 */
#[Unguarded]
class Refund extends Model
{
    use AutoCreateOrderNo,
        BelongsToUser,
        BelongsToOrder,
        BelongsToTenant,
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
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'no';
    }

    /**
     * 退款明细
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(RefundItem::class);
    }

    /**
     * 退款日志
     *
     * @return HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(RefundLog::class);
    }

    /**
     * 物流信息
     *
     * @return HasOne
     */
    public function express(): HasOne
    {
        return $this->hasOne(RefundExpress::class);
    }

    /**
     * 退款完成处理
     *
     * @param  bool  $result
     * @param  string|null  $desc
     * @param  array|null  $data
     * @return void
     */
    public function refunded(bool $result, ?string $desc = null, ?array $data = null): void
    {
    }
}
