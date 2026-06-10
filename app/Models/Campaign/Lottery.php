<?php

namespace App\Models\Campaign;

use App\Enums\Campaign\LotteryDrawMode;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Models\User\User;
use App\Policies\Campaign\LotteryPolicy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(LotteryPolicy::class)]
class Lottery extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        SoftDeletes;

    protected $casts = [
        'draw_mode' => LotteryDrawMode::class,
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    /**
     * 关联奖品
     */
    public function prizes(): HasMany
    {
        return $this->hasMany(LotteryPrize::class);
    }

    /**
     * 关联奖品发放记录
     */
    public function prizeRecords(): HasMany
    {
        return $this->hasMany(LotteryPrizeRecord::class);
    }

    /**
     * 活动是否进行中（已启用 + 时间范围内）
     */
    public function isActive(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $now = now();

        if ($this->start_at && $now->isBefore($this->start_at)) {
            return false;
        }

        if ($this->end_at && $now->isAfter($this->end_at)) {
            return false;
        }

        return true;
    }

    /**
     * 活动是否已过期
     */
    public function isExpired(): bool
    {
        return $this->end_at && now()->isAfter($this->end_at);
    }

    /**
     * 获取用户剩余抽奖次数
     */
    public function getAvailableDrawsForUser(User $user): int
    {
        $totalUsed = $this->draws()
            ->where('user_id', $user->getKey())
            ->count();

        // 总次数限制
        if ($this->max_draws_per_user !== null) {
            $totalRemaining = $this->max_draws_per_user - $totalUsed;

            if ($totalRemaining <= 0) {
                return 0;
            }
        }

        // 免费模式：仅限今日免费次数
        if ($this->draw_mode === LotteryDrawMode::Free) {
            $todayFreeUsed = $this->draws()
                ->where('user_id', $user->getKey())
                ->where('draw_cost_type', 'free')
                ->whereDate('created_at', now()->toDateString())
                ->count();

            $freeRemaining = $this->free_draws_per_day - $todayFreeUsed;

            return $this->max_draws_per_user !== null
                ? max(0, min($freeRemaining, $totalRemaining ?? PHP_INT_MAX))
                : max(0, $freeRemaining);
        }

        // 积分模式：不限次数（受 max_draws_per_user 约束）
        return $this->max_draws_per_user !== null ? $totalRemaining : PHP_INT_MAX;
    }

    /**
     * 关联抽奖记录
     */
    public function draws(): HasMany
    {
        return $this->hasMany(LotteryDraw::class);
    }

    /**
     * 进行中的活动作用域
     */
    #[Scope]
    protected function ofActive(Builder $query): void
    {
        $query->ofEnabled()
            ->where(function (Builder $builder) {
                $builder->whereNull('start_at')
                    ->orWhere('start_at', '<=', now());
            })
            ->where(function (Builder $builder) {
                $builder->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            });
    }
}
