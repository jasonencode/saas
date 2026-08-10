<?php

namespace App\Models\Campaign;

use App\Enums\Campaign\LotteryPrizeStatus;
use App\Enums\Campaign\LotteryPrizeType;
use App\Models\Model;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
class LotteryPrizeRecord extends Model
{
    protected function casts(): array
    {
        return [
            'type' => LotteryPrizeType::class,
            'status' => LotteryPrizeStatus::class,
            'prize_detail' => 'json',
            'fulfilled_at' => 'datetime',
        ];
    }

    /**
     * 关联抽奖记录
     *
     * @return BelongsTo<LotteryDraw>
     */
    public function draw(): BelongsTo
    {
        return $this->belongsTo(LotteryDraw::class, 'lottery_draw_id');
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
     * 所属用户
     *
     * @return BelongsTo<User>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)
            ->withoutGlobalScopes();
    }

    /**
     * 中奖奖品
     *
     * @return BelongsTo<LotteryPrize>
     */
    public function prize(): BelongsTo
    {
        return $this->belongsTo(LotteryPrize::class, 'lottery_prize_id');
    }
}
