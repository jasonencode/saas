<?php

namespace App\Services\Campaign;

use App\Contracts\ServiceInterface;
use App\Enums\Campaign\LotteryDrawMode;
use App\Enums\Campaign\LotteryPrizeStatus;
use App\Enums\Campaign\LotteryPrizeType;
use App\Enums\Finance\AccountAssetType;
use App\Enums\User\UserAccountLogType;
use App\Models\Campaign\Coupon;
use App\Models\Campaign\Lottery;
use App\Models\Campaign\LotteryDraw;
use App\Models\Campaign\LotteryPrize;
use App\Models\Campaign\LotteryPrizeRecord;
use App\Models\User\User;
use App\Services\Finance\UserAccountService;
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
            $costAmount = $costType === 'points' ? (float) $lottery->points_per_draw : 0;

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

            // 积分模式扣减积分（余额不足抛异常，事务回滚奖品与库存）
            if ($costAmount > 0) {
                $this->deductPoints($lottery, $user, $draw, $costAmount);
            }

            // 创建奖品发放记录（非 none 奖品），虚拟奖品即时发放
            if ($selectedPrize->type !== LotteryPrizeType::None) {
                $record = LotteryPrizeRecord::create([
                    'lottery_draw_id' => $draw->getKey(),
                    'lottery_id' => $lottery->getKey(),
                    'user_id' => $user->getKey(),
                    'lottery_prize_id' => $selectedPrize->getKey(),
                    'type' => $selectedPrize->type,
                    'prize_detail' => $selectedPrize->prize_config,
                    'status' => LotteryPrizeStatus::Pending,
                ]);

                $this->autoFulfillPrize($record);
            }

            return $draw->load(['prize', 'prizeRecord']);
        });
    }

    /**
     * 判断消耗类型
     *
     * @param  Lottery  $lottery  抽奖活动
     * @param  User  $user  用户
     *
     * @throws InvalidArgumentException 无可用抽奖次数
     *
     * @return string 消耗类型（free 或 points）
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
     * 获取符合条件的奖品列表
     *
     * @param  Lottery  $lottery  抽奖活动
     * @param  User  $user  用户
     *
     * @return Collection<int, LotteryPrize> 符合条件的奖品列表
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
     *
     * @param  Collection<int, LotteryPrize>  $prizes  奖品列表
     *
     * @return LotteryPrize 选中的奖品
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
     *
     * @param  LotteryPrize  $prize  奖品
     *
     * @return bool 是否扣减成功
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
     * 扣减抽奖消耗的积分
     *
     * @param  Lottery  $lottery  抽奖活动
     * @param  User  $user  用户
     * @param  LotteryDraw  $draw  抽奖记录
     * @param  float  $amount  消耗积分
     *
     * @throws InvalidArgumentException 积分不足
     * @throws Throwable 事务异常
     */
    protected function deductPoints(Lottery $lottery, User $user, LotteryDraw $draw, float $amount): void
    {
        $account = $user->account()->firstOrCreate([]);

        service(UserAccountService::class)->modifyAsset(
            account: $account,
            asset: AccountAssetType::Points,
            amount: -$amount,
            remark: '抽奖消耗 - '.$lottery->name,
            source: $draw,
            type: UserAccountLogType::Consume,
        );
    }

    /**
     * 自动发放虚拟奖品（余额 / 积分 / 优惠券）
     *
     * 实物与红包奖品保持待兑奖；配置异常时同样保留记录，交由运营人工处理。
     *
     * @param  LotteryPrizeRecord  $record  奖品记录
     */
    protected function autoFulfillPrize(LotteryPrizeRecord $record): void
    {
        match ($record->type) {
            LotteryPrizeType::Balance,
            LotteryPrizeType::Points => $this->tryGrantAssetPrize($record),
            LotteryPrizeType::Coupon => $this->tryFulfillCouponPrize($record),
            default => null,
        };
    }

    /**
     * 尝试发放资产类奖品，失败时保留待兑奖状态
     *
     * @param  LotteryPrizeRecord  $record  奖品记录
     */
    protected function tryGrantAssetPrize(LotteryPrizeRecord $record): void
    {
        try {
            $this->grantAssetPrize($record);
        } catch (InvalidArgumentException) {
            // 金额未配置、用户不存在等异常保留待兑奖，供人工处理
        }
    }

    /**
     * 尝试发放优惠券奖品，失败时保留待兑奖状态
     *
     * @param  LotteryPrizeRecord  $record  奖品记录
     */
    protected function tryFulfillCouponPrize(LotteryPrizeRecord $record): void
    {
        try {
            $this->fulfillCouponPrize($record, '系统自动发放');
        } catch (InvalidArgumentException) {
            // 券配置缺失 / 已失效等异常保留待兑奖，供人工处理
        }
    }

    /**
     * 发放余额 / 积分奖品
     *
     * @param  LotteryPrizeRecord  $record  奖品记录
     * @param  string|null  $note  兑奖备注
     *
     * @throws InvalidArgumentException 奖品状态或金额不可发放
     * @throws Throwable 事务异常
     */
    protected function grantAssetPrize(LotteryPrizeRecord $record, ?string $note = null): void
    {
        if ($record->status !== LotteryPrizeStatus::Pending) {
            throw new InvalidArgumentException('该奖品不可兑奖');
        }

        $detail = $record->prize_detail ?? [];
        $amount = (float) ($detail['amount'] ?? 0);

        if ($amount <= 0) {
            throw new InvalidArgumentException('奖品金额未配置');
        }

        $user = $record->user;

        if (!$user) {
            throw new InvalidArgumentException('中奖用户不存在');
        }

        $asset = $record->type === LotteryPrizeType::Balance
            ? AccountAssetType::Balance
            : AccountAssetType::Points;

        service(UserAccountService::class)->modifyAsset(
            account: $user->account()->firstOrCreate([]),
            asset: $asset,
            amount: $amount,
            remark: '抽奖奖品 - '.($record->prize?->name ?? $record->type->getLabel()),
            source: $record,
            type: UserAccountLogType::Reward,
        );

        $this->markFulfilled($record, $note ?? '系统自动发放');
    }

    /**
     * 标记奖品为已兑奖
     *
     * @param  LotteryPrizeRecord  $record  奖品记录
     * @param  string|null  $note  兑奖备注
     */
    protected function markFulfilled(LotteryPrizeRecord $record, ?string $note = null): void
    {
        $record->update([
            'status' => LotteryPrizeStatus::Fulfilled,
            'fulfillment_note' => $note,
            'fulfilled_at' => now(),
        ]);
    }

    /**
     * 获取谢谢参与奖品
     *
     * @param  Lottery  $lottery  抽奖活动
     *
     * @return LotteryPrize|null 谢谢参与奖品
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
     * 兑奖（实物奖品线下兑付，虚拟奖品用于补发）
     *
     * @param  LotteryPrizeRecord  $record  奖品记录
     * @param  string|null  $note  兑奖备注
     *
     * @throws InvalidArgumentException 奖品类型不支持或状态不允许兑奖
     * @throws Throwable 事务异常
     */
    public function fulfillPrize(LotteryPrizeRecord $record, ?string $note = null): void
    {
        DB::transaction(function () use ($record, $note) {
            // 行锁重读，避免并发重复发放
            $locked = LotteryPrizeRecord::whereKey($record->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== LotteryPrizeStatus::Pending) {
                throw new InvalidArgumentException('该奖品不可兑奖');
            }

            // 优惠券奖品：发放券实例
            if ($locked->type === LotteryPrizeType::Coupon) {
                $this->fulfillCouponPrize($locked, $note);

                return;
            }

            // 余额 / 积分奖品：发放资产（用于自动发放失败后的补发）
            if (in_array($locked->type, [LotteryPrizeType::Balance, LotteryPrizeType::Points], true)) {
                $this->grantAssetPrize($locked, $note);

                return;
            }

            // 实物奖品由商家线下兑付
            if ($locked->type === LotteryPrizeType::Physical) {
                $this->markFulfilled($locked, $note);

                return;
            }

            throw new InvalidArgumentException('该奖品不支持兑奖');
        });

        $record->refresh();
    }

    /**
     * 兑付优惠券奖品
     *
     * 读取 prize_detail.coupon_id 向中奖用户发放券实例，成功后置为已兑付；
     * 券停用 / 已达发放上限等失败时抛异常并保持待兑付，供人工处理（换券或取消奖品）。
     *
     * @param  LotteryPrizeRecord  $record  奖品记录
     * @param  string|null  $note  兑奖备注
     *
     * @throws InvalidArgumentException 奖品不可兑、券配置缺失或发放失败
     * @throws Throwable 事务异常
     */
    private function fulfillCouponPrize(LotteryPrizeRecord $record, ?string $note): void
    {
        if ($record->status !== LotteryPrizeStatus::Pending) {
            throw new InvalidArgumentException('该奖品不可兑奖');
        }

        $detail = $record->prize_detail ?? [];
        $couponId = (int) ($detail['coupon_id'] ?? 0);

        if ($couponId < 1) {
            throw new InvalidArgumentException('奖品未配置优惠券');
        }

        $coupon = Coupon::find($couponId);

        if (!$coupon) {
            throw new InvalidArgumentException('奖品优惠券不存在或已删除');
        }

        $user = $record->user;

        if (!$user) {
            throw new InvalidArgumentException('中奖用户不存在');
        }

        DB::transaction(function () use ($record, $note, $coupon, $user) {
            service(CouponService::class)->sendToUser($coupon, $user);

            $record->update([
                'status' => LotteryPrizeStatus::Fulfilled,
                'fulfillment_note' => $note,
                'fulfilled_at' => now(),
            ]);
        });
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
        DB::transaction(static function () use ($record, $reason) {
            // 行锁重读，避免并发重复取消导致库存超增
            $locked = LotteryPrizeRecord::whereKey($record->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== LotteryPrizeStatus::Pending) {
                throw new InvalidArgumentException('该奖品不可取消');
            }

            $locked->update([
                'status' => LotteryPrizeStatus::Cancelled,
                'fulfillment_note' => $reason,
            ]);

            // 回增库存，且不超过总数量
            if ($locked->lottery_prize_id) {
                DB::table('lottery_prizes')
                    ->where('id', $locked->lottery_prize_id)
                    ->where('total_quantity', '>', 0)
                    ->whereColumn('remaining_quantity', '<', 'total_quantity')
                    ->increment('remaining_quantity');
            }
        });

        $record->refresh();
    }
}
