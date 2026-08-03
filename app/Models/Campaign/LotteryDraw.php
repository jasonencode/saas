<?php

namespace App\Models\Campaign;

use App\Models\Model;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Unguarded]
class LotteryDraw extends Model
{
    protected function casts(): array
    {
        return [
            'ip_address' => 'string',
        ];
    }

    /**
     * 所属活动
     */
    public function lottery(): BelongsTo
    {
        return $this->belongsTo(Lottery::class);
    }

    /**
     * 所属用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)
            ->withoutGlobalScopes();
    }

    /**
     * 中奖奖品（可空）
     */
    public function prize(): BelongsTo
    {
        return $this->belongsTo(LotteryPrize::class, 'lottery_prize_id');
    }

    /**
     * 奖品发放记录
     */
    public function prizeRecord(): HasOne
    {
        return $this->hasOne(LotteryPrizeRecord::class, 'lottery_draw_id');
    }
}
