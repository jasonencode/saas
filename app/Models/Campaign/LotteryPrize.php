<?php

namespace App\Models\Campaign;

use App\Enums\Campaign\LotteryPrizeType;
use App\Models\Model;
use App\Models\Traits\HasSortable;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
class LotteryPrize extends Model
{
    use HasSortable,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => LotteryPrizeType::class,
            'prize_config' => 'json',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        // 新建奖品未显式指定库存时，按总数量初始化剩余数量
        self::creating(static function (LotteryPrize $prize) {
            if (!array_key_exists('remaining_quantity', $prize->getAttributes())) {
                $prize->remaining_quantity = (int) $prize->total_quantity;
            }
        });

        // 调整总数量时按差额同步剩余数量；0 表示无限量
        self::updating(static function (LotteryPrize $prize) {
            if (!$prize->isDirty('total_quantity') || $prize->isDirty('remaining_quantity')) {
                return;
            }

            $total = (int) $prize->total_quantity;

            if ($total === 0) {
                $prize->remaining_quantity = 0;

                return;
            }

            $delta = $total - (int) $prize->getOriginal('total_quantity');
            $prize->remaining_quantity = max(0, (int) $prize->remaining_quantity + $delta);
        });
    }

    /**
     * 所属活动
     *
     * @return BelongsTo<Lottery>
     */
    public function lottery(): BelongsTo
    {
        return $this->belongsTo(Lottery::class);
    }

    /**
     * 奖品是否可用（库存充足）
     *
     * @return bool 是否可用
     */
    public function isAvailable(): bool
    {
        // total_quantity 为 0 表示无限量
        if ($this->total_quantity === 0) {
            return true;
        }

        return $this->remaining_quantity > 0;
    }

    /**
     * 用户是否已达领取上限
     *
     * @param  User  $user  查询用户
     *
     * @return bool 是否已达上限
     */
    public function hasUserReachedLimit(User $user): bool
    {
        if ($this->user_limit === null) {
            return false;
        }

        $wonCount = LotteryPrizeRecord::where('lottery_prize_id', $this->getKey())
            ->where('user_id', $user->getKey())
            ->count();

        return $wonCount >= $this->user_limit;
    }
}
