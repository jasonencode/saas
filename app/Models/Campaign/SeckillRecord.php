<?php

namespace App\Models\Campaign;

use App\Enums\Campaign\SeckillRecordStatus;
use App\Models\Mall\Order;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 秒杀资格与成交记录
 *
 * 负责限购统计、下单幂等与取消/退款回补。
 *
 * @property SeckillRecordStatus $status
 */
#[Unguarded]
class SeckillRecord extends Model
{
    use BelongsToTenant,
        BelongsToUser;

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'price' => 'decimal:2',
            'status' => SeckillRecordStatus::class,
        ];
    }

    /**
     * 所属场次
     *
     * @return BelongsTo<SeckillSession>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(SeckillSession::class, 'session_id');
    }

    /**
     * 关联订单
     *
     * @return BelongsTo<Order>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
