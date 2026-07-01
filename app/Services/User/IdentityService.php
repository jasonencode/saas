<?php

namespace App\Services\User;

use App\Contracts\ServiceInterface;
use App\Enums\User\IdentityChannel;
use App\Enums\User\IdentityOrderStatus;
use App\Events\User\IdentityChanged;
use App\Events\User\IdentityExpired;
use App\Events\User\IdentityPurchased;
use App\Models\Mall\Order as MallOrder;
use App\Models\User\Identity;
use App\Models\User\IdentityLog;
use App\Models\User\IdentityOrder;
use App\Models\User\User;
use App\Models\User\UserIdentity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class IdentityService implements ServiceInterface
{
    /**
     * 用户添加身份
     */
    public function entry(
        User $user,
        Identity $identity,
        IdentityChannel $channel = IdentityChannel::Auto,
        int $qty = 1,
        array $source = []
    ): void {
        $pivot = UserIdentity::where('user_id', $user->getKey())->where('identity_id', $identity->getKey())->first();

        $data['end_at'] = match (true) {
            $pivot && $identity->days => $this->parseEndedAtTime(Carbon::parse($pivot->end_at)->addDays($identity->days * $qty)),
            !$pivot && $identity->days => $this->parseEndedAtTime(Carbon::now()->addDays($identity->days * $qty)),
            default => null
        };

        $data['serial'] = $pivot ? $pivot->serial : UserIdentity::getNewestSerialNo($identity);
        !$pivot && $data['start_at'] = now();

        $before = null;
        if (!config('custom.identity.allow_multiple')) {
            $before = $user->identities()->first();
            $user->identities()->syncWithPivotValues([$identity->getKey()], $data);
        } elseif ($pivot) {
            $user->identities()->updateExistingPivot($identity->getKey(), $data);
        } else {
            $user->identities()->attach($identity->getKey(), $data);
        }

        $this->generateIdentityLog($user, $before, $identity, $channel, $source);
        IdentityChanged::dispatch($user, $before, $identity, $channel);
    }

    /**
     * 解析结束时间
     */
    private function parseEndedAtTime(Carbon $endedAT): Carbon
    {
        $maxDate = Carbon::create(9999, 12, 31, 23, 59, 59);
        if ($endedAT->greaterThan($maxDate)) {
            return $maxDate;
        }

        return $endedAT;
    }

    /**
     * 生成身份变更日志
     */
    private function generateIdentityLog(
        User $user,
        ?Identity $before,
        ?Identity $after,
        IdentityChannel $channel = IdentityChannel::Auto,
        array $source = []
    ): void {
        IdentityLog::create([
            'user' => $user,
            'before' => $before?->getKey() ?? 0,
            'after' => $after?->getKey() ?? 0,
            'channel' => $channel,
            'source' => $source,
        ]);
    }

    /**
     * 用户移除身份
     */
    public function remove(
        User $user,
        Identity $identity,
        IdentityChannel $channel = IdentityChannel::Auto,
        array $source = []
    ): void {
        $pivot = UserIdentity::where('user_id', $user->getKey())
            ->where('identity_id', $identity->getKey())
            ->first();

        if (!$pivot) {
            return;
        }

        $user->identities()->detach($identity->getKey());
        $this->generateIdentityLog($user, $identity, null, $channel, $source);
        IdentityChanged::dispatch($user, $identity, null, $channel);
    }

    /**
     * 移除用户过期的身份
     */
    public function removeExpiredForUser(
        User $user,
        IdentityChannel $channel = IdentityChannel::Auto
    ): int {
        $expired = $user->identities()
            ->wherePivotNotNull('end_at')
            ->wherePivot('end_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($expired as $item) {
            $user->identities()->detach($item->getKey());
            $this->generateIdentityLog($user, $item, null, $channel, ['reason' => 'expired']);
            IdentityExpired::dispatch($user, $item);
            $count++;
        }

        return $count;
    }

    // ================================================================
    // 付费订阅流程
    // ================================================================

    /**
     * 创建身份订阅订单
     */
    public function purchase(
        User $user,
        Identity $identity,
        float $amount,
        int $qty = 1
    ): IdentityOrder {
        $order = IdentityOrder::create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->getKey(),
            'identity_id' => $identity->getKey(),
            'qty' => $qty,
            'amount' => $amount,
            'status' => IdentityOrderStatus::Pending,
        ]);

        IdentityPurchased::dispatch($order);

        return $order;
    }

    /**
     * 订单支付完成，授予身份（已持有则自动续期）
     */
    public function payOrder(IdentityOrder $order): void
    {
        if ($order->status !== IdentityOrderStatus::Pending) {
            throw new RuntimeException('订单状态异常，仅待支付订单可执行支付操作');
        }

        $order->update(['status' => IdentityOrderStatus::Paid]);

        $user = $order->user;
        $identity = $order->identity;
        $source = [
            'order_id' => $order->getKey(),
            'order_no' => $order->no,
        ];

        // 已持有且有时效身份 → 续期；否则 → 新授予
        if ($this->has($user, $identity) && $identity->days) {
            $this->renew($user, $identity, $order->qty, IdentityChannel::Subscribe, $source);
        } else {
            $this->entry($user, $identity, IdentityChannel::Subscribe, $order->qty, $source);
        }
    }

    // ================================================================
    // 续期/延期
    // ================================================================

    /**
     * 续期用户身份
     *
     * @throws RuntimeException 当用户未持有该身份时抛出
     */
    public function renew(
        User $user,
        Identity $identity,
        int $qty = 1,
        IdentityChannel $channel = IdentityChannel::Subscribe,
        array $source = []
    ): void {
        $pivot = UserIdentity::where('user_id', $user->getKey())
            ->where('identity_id', $identity->getKey())
            ->first();

        if (!$pivot) {
            throw new RuntimeException('用户未持有该身份，无法续期');
        }

        if (!$identity->days) {
            throw new RuntimeException('该身份为永久身份，无需续期');
        }

        $baseTime = ($pivot->end_at && $pivot->end_at->isFuture())
            ? Carbon::parse($pivot->end_at)
            : Carbon::now();

        $newEndAt = $this->parseEndedAtTime($baseTime->addDays($identity->days * $qty));

        $user->identities()->updateExistingPivot($identity->getKey(), [
            'end_at' => $newEndAt,
        ]);

        $this->generateIdentityLog($user, $identity, $identity, $channel, array_merge($source, [
            'action' => 'renew',
            'qty' => $qty,
        ]));
    }

    // ================================================================
    // 查询服务层
    // ================================================================

    /**
     * 用户是否持有指定身份（含有效期内判断）
     */
    public function has(User $user, Identity $identity): bool
    {
        return UserIdentity::where('user_id', $user->getKey())
            ->where('identity_id', $identity->getKey())
            ->where(function (Builder $query) {
                $query->whereNull('end_at')
                    ->orWhere('end_at', '>', now());
            })
            ->exists();
    }

    /**
     * 用户当前所有有效身份
     *
     * @return Collection<int, Identity>
     */
    public function activeIdentities(User $user): Collection
    {
        return $user->identities()
            ->wherePivot(function (Builder $query) {
                $query->whereNull('end_at')
                    ->orWhere('end_at', '>', now());
            })
            ->get();
    }

    /**
     * 用户即将过期的身份
     *
     * @return Collection<int, Identity>
     */
    public function expiringSoon(User $user, int $days = 7): Collection
    {
        $deadline = Carbon::now()->addDays($days);

        return $user->identities()
            ->wherePivotNotNull('end_at')
            ->wherePivot('end_at', '>', now())
            ->wherePivot('end_at', '<=', $deadline)
            ->get();
    }

    // ================================================================
    // 条件/规则执行引擎
    // ================================================================

    /**
     * 评估用户是否满足身份的升级条件
     *
     * 支持的条件键:
     * - min_orders: 最少订单数
     * - min_amount: 最低消费金额
     * - min_days: 注册天数
     */
    public function evaluateConditions(User $user, Identity $identity): bool
    {
        $conditions = $identity->conditions;

        if (empty($conditions)) {
            return true;
        }

        if (isset($conditions['min_orders'])) {
            $orderCount = MallOrder::where('user_id', $user->getKey())
                ->where('tenant_id', $user->tenant_id)
                ->where('status', 'completed')
                ->count();

            if ($orderCount < $conditions['min_orders']) {
                return false;
            }
        }

        if (isset($conditions['min_amount'])) {
            $totalAmount = MallOrder::where('user_id', $user->getKey())
                ->where('tenant_id', $user->tenant_id)
                ->where('status', 'completed')
                ->sum('amount');

            if (bccomp($totalAmount, (string) $conditions['min_amount']) < 0) {
                return false;
            }
        }

        if (isset($conditions['min_days'])) {
            $registeredDays = $user->created_at->diffInDays(Carbon::now());

            if ($registeredDays < $conditions['min_days']) {
                return false;
            }
        }

        return true;
    }

    /**
     * 检查条件并自动授予满足条件的身份
     */
    public function checkAndAssign(
        User $user,
        IdentityChannel $channel = IdentityChannel::Auto
    ): void {
        $identities = Identity::where('tenant_id', $user->tenant_id)
            ->where('can_subscribe', true)
            ->where('status', true)
            ->whereNotNull('conditions')
            ->get();

        foreach ($identities as $identity) {
            if ($this->has($user, $identity)) {
                continue;
            }

            if ($this->evaluateConditions($user, $identity)) {
                $this->entry($user, $identity, $channel, 1, ['reason' => 'auto_assign']);
            }
        }
    }

    // ================================================================
    // 批量操作
    // ================================================================

    /**
     * 批量授予身份
     */
    public function batchEntry(
        array $users,
        Identity $identity,
        IdentityChannel $channel = IdentityChannel::Auto,
        array $source = []
    ): int {
        $count = 0;
        foreach ($users as $user) {
            try {
                $this->entry($user, $identity, $channel, 1, $source);
                $count++;
            } catch (\Throwable $e) {
                Log::warning("批量授予身份失败 [user={$user->getKey()}]: {$e->getMessage()}");
            }
        }

        return $count;
    }

    /**
     * 批量移除身份
     */
    public function batchRemove(
        array $users,
        Identity $identity,
        IdentityChannel $channel = IdentityChannel::Auto,
        array $source = []
    ): int {
        $count = 0;
        foreach ($users as $user) {
            try {
                $this->remove($user, $identity, $channel, $source);
                $count++;
            } catch (\Throwable $e) {
                Log::warning("批量移除身份失败 [user={$user->getKey()}]: {$e->getMessage()}");
            }
        }

        return $count;
    }
}
