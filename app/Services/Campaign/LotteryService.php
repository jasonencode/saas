<?php

namespace App\Services\Campaign;

use App\Contracts\ServiceInterface;
use App\Enums\Campaign\LotteryDrawMode;
use App\Enums\Campaign\LotteryPrizeStatus;
use App\Enums\Campaign\LotteryPrizeType;
use App\Models\Campaign\Lottery;
use App\Models\Campaign\LotteryDraw;
use App\Models\Campaign\LotteryPrize;
use App\Models\Campaign\LotteryPrizeRecord;
use App\Models\User\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class LotteryService implements ServiceInterface
{
    /**
     * 执行抽奖
     *
     * @param  Lottery  $lottery  抽奖活动
     * @param  User  $user  用户
     * @param  string|null  $ip  用户 IP
     * @param  string|null  $userAgent  用户 UA
     *
     * @throws Throwable 事务异常
     * @throws InvalidArgumentException 活动不可用、次数不足或奖品售罄
     *
     * @return LotteryDraw 抽奖记录
     */
    public function draw(Lottery $lottery, User $user, ?string $ip = null, ?string $userAgent = null): LotteryDraw
    {
        // 1. 校验活动状态
        if (!$lottery->isActive()) {
            throw new InvalidArgumentException('活动未开始或已结束');
        }

        // 2. 检查剩余抽奖次数
        $available = $lottery->getAvailableDrawsForUser($user);

        if ($available <= 0) {
            throw new InvalidArgumentException('抽奖次数已用完');
        }

        // 3. 判断消耗类型
        $costType = $this->resolveCostType($lottery, $user);

        // 4. 加载 eligible 奖品
        $prizes = $this->getEligiblePrizes($lottery, $user);

        if ($prizes->isEmpty()) {
            throw new InvalidArgumentException('暂无可用奖品');
        }

        // 5. 权重随机选择
        $selectedPrize = $this->pickPrize($prizes);

        // 6. 事务内完成抽奖
        return DB::transaction(function () use ($lottery, $user, $costType, $selectedPrize, $ip, $userAgent) {
            // 原子扣减库存（非 none 奖品）
            if ($selectedPrize->type !== LotteryPrizeType::None) {
                $decremented = $this->decrementQuantity($selectedPrize);

                if (!$decremented) {
                    // 售罄，回退到谢谢参与
                    $selectedPrize = $this->getNonePrize($lottery);

                    if (!$selectedPrize) {
                        throw new InvalidArgumentException('奖品已售罄');
                    }
                }
            }

            // 计算消耗金额
            $costAmount = $costType === 'points' ? $lottery->points_per_draw : 0;

            // 创建抽奖记录
            $draw = LotteryDraw::create([
                'lottery_id' => $lottery->getKey(),
                'user_id' => $user->getKey(),
                'lottery_prize_id' => $selectedPrize->type !== LotteryPrizeType::None
                    ? $selectedPrize->getKey()
                    : null,
                'draw_cost_type' => $costType,
                'draw_cost_amount' => $costAmount,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);

            // 创建奖品发放记录（非 none 奖品）
            if ($selectedPrize->type !== LotteryPrizeType::None) {
                LotteryPrizeRecord::create([
                    'lottery_draw_id' => $draw->getKey(),
                    'lottery_id' => $lottery->getKey(),
                    'user_id' => $user->getKey(),
                    'lottery_prize_id' => $selectedPrize->getKey(),
                    'type' => $selectedPrize->type,
                    'prize_detail' => $selectedPrize->prize_config,
                    'status' => LotteryPrizeStatus::Pending,
                ]);
            }

            return $draw->load(['prize', 'prizeRecord']);
        });
    }

    /**
     * 判断消耗类型
     */
    protected function resolveCostType(Lottery $lottery, User $user): string
    {
        // 免费模式：检查今日免费次数
        if ($lottery->draw_mode === LotteryDrawMode::Free && $lottery->free_draws_per_day > 0) {
            $todayFreeUsed = $lottery->draws()
                ->where('user_id', $user->getKey())
                ->where('draw_cost_type', 'free')
                ->whereDate('created_at', now()->toDateString())
                ->count();

            if ($todayFreeUsed < $lottery->free_draws_per_day) {
                return 'free';
            }
        }

        // 积分模式：消耗积分
        if ($lottery->draw_mode === LotteryDrawMode::Points && $lottery->points_per_draw > 0) {
            return 'points';
        }

        throw new InvalidArgumentException('无可用抽奖次数');
    }

    /**
     * 获取 eligible 奖品列表
     */
    protected function getEligiblePrizes(Lottery $lottery, User $user): Collection
    {
        return $lottery->prizes()
            ->get()
            ->filter(function (LotteryPrize $prize) use ($user) {
                return $prize->isAvailable() && !$prize->hasUserReachedLimit($user);
            })
            ->values();
    }

    /**
     * 权重随机选择奖品
     */
    protected function pickPrize(Collection $prizes): LotteryPrize
    {
        $totalWeight = $prizes->sum('weight');

        if ($totalWeight <= 0) {
            return $prizes->first();
        }

        $rand = random_int(0, $totalWeight - 1);
        $cumulative = 0;

        foreach ($prizes as $prize) {
            $cumulative += $prize->weight;

            if ($rand < $cumulative) {
                return $prize;
            }
        }

        return $prizes->last();
    }

    /**
     * 原子扣减库存
     */
    protected function decrementQuantity(LotteryPrize $prize): bool
    {
        if ($prize->total_quantity === 0) {
            // 无限量
            return true;
        }

        $affected = DB::table('lottery_prizes')
            ->where('id', $prize->getKey())
            ->where('remaining_quantity', '>', 0)
            ->decrement('remaining_quantity');

        return $affected > 0;
    }

    /**
     * 获取谢谢参与奖品
     */
    protected function getNonePrize(Lottery $lottery): ?LotteryPrize
    {
        return $lottery->prizes()
            ->where('type', LotteryPrizeType::None)
            ->first();
    }

    /**
     * 获取用户剩余抽奖次数
     *
     * @param  Lottery  $lottery  抽奖活动
     * @param  User  $user  用户
     *
     * @return int 剩余次数
     */
    public function getAvailableDraws(Lottery $lottery, User $user): int
    {
        return $lottery->getAvailableDrawsForUser($user);
    }

    /**
     * 兑奖（实物奖品）
     *
     * @param  LotteryPrizeRecord  $record  奖品记录
     * @param  string|null  $note  兑奖备注
     *
     * @throws InvalidArgumentException 非实物奖品或状态不允许兑奖
     */
    public function fulfillPrize(LotteryPrizeRecord $record, ?string $note = null): void
    {
        if ($record->type !== LotteryPrizeType::Physical) {
            throw new InvalidArgumentException('仅实物奖品需要兑奖');
        }

        if ($record->status !== LotteryPrizeStatus::Pending) {
            throw new InvalidArgumentException('该奖品不可兑奖');
        }

        $record->update([
            'status' => LotteryPrizeStatus::Fulfilled,
            'fulfillment_note' => $note,
            'fulfilled_at' => now(),
        ]);
    }

    /**
     * 取消奖品
     *
     * @param  LotteryPrizeRecord  $record  奖品记录
     * @param  string|null  $reason  取消原因
     *
     * @throws InvalidArgumentException 状态不允许取消
     * @throws Throwable 事务异常
     */
    public function cancelPrize(LotteryPrizeRecord $record, ?string $reason = null): void
    {
        if ($record->status !== LotteryPrizeStatus::Pending) {
            throw new InvalidArgumentException('该奖品不可取消');
        }

        DB::transaction(static function () use ($record, $reason) {
            $record->update([
                'status' => LotteryPrizeStatus::Cancelled,
                'fulfillment_note' => $reason,
            ]);

            // 回增库存
            if ($record->prize && $record->prize->total_quantity > 0) {
                $record->prize->increment('remaining_quantity');
            }
        });
    }
}
