<?php

namespace App\Services\User;

use App\Contracts\ServiceInterface;
use App\Enums\Mall\FulfillmentType;
use App\Enums\User\IdentityChannel;
use App\Events\User\IdentityChanged;
use App\Events\User\IdentityExpired;
use App\Models\Mall\Order as MallOrder;
use App\Models\System\System;
use App\Models\System\Tenant;
use App\Models\User\Identity;
use App\Models\User\IdentityLog;
use App\Models\User\User;
use App\Models\User\UserIdentity;
use App\Services\Mall\DTOs\OrderItemDto;
use App\Services\Mall\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class IdentityService implements ServiceInterface
{
    /**
     * 移除用户过期的身份
     *
     * @param  User  $user  用户
     * @param  IdentityChannel  $channel  变更渠道
     *
     * @return int 移除的数量
     */
    public function removeExpiredForUser(
        User $user,
        IdentityChannel $channel = IdentityChannel::Auto
    ): int {
        $expired = $user->identities()
            ->whereNotNull('user_identity.end_at')
            ->where('user_identity.end_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($expired as $item) {
            $user->identities()->detach($item->getKey());
            $this->generateIdentityLog(
                user: $user,
                tenantId: $item->tenant_id,
                channel: $channel,
                before: $item,
                source: [
                    'reason' => 'expired',
                    'operator_type' => System::class,
                    'operator_id' => 3,
                ],
            );
            IdentityExpired::dispatch($user, $item);
            $count++;
        }

        return $count;
    }

    /**
     * 生成身份变更日志
     *
     * @param  User  $user  用户
     * @param  int  $tenantId  租户ID
     * @param  IdentityChannel  $channel  变更渠道
     * @param  Identity|null  $before  变更前的身份
     * @param  Identity|null  $after  变更后的身份
     * @param  array  $source  来源信息
     */
    private function generateIdentityLog(
        User $user,
        int $tenantId,
        IdentityChannel $channel,
        ?Identity $before = null,
        ?Identity $after = null,
        array $source = []
    ): void {
        IdentityLog::create([
            'user' => $user,
            'tenant_id' => $tenantId,
            'before' => $before?->getKey() ?? 0,
            'after' => $after?->getKey() ?? 0,
            'channel' => $channel,
            'source' => $source,
        ]);
    }

    /**
     * 创建身份订阅订单（走商城统一下单流程）
     *
     * @param  User  $user  用户
     * @param  Identity  $identity  身份
     * @param  int  $qty  数量
     *
     * @throws RuntimeException|\Throwable 身份不可订阅或下单失败
     *
     * @return MallOrder 创建的商城订单
     */
    public function purchase(
        User $user,
        Identity $identity,
        int $qty = 1
    ): MallOrder {
        if ($reason = $identity->checkOrderable($qty)) {
            throw new RuntimeException($reason);
        }

        if ($identity->is_unique && $this->has($user, $identity)) {
            throw new RuntimeException('您已持有该身份，无需重复订阅');
        }

        $tenant = Tenant::find($identity->tenant_id);
        if (!$tenant) {
            throw new RuntimeException("租户不存在: $identity->tenant_id");
        }

        return app(OrderService::class)->createOrder(
            $tenant,
            $user,
            [new OrderItemDto($identity, $qty)],
            FulfillmentType::Virtual,
        );
    }

    /**
     * 商城订单支付完成后，授予身份（已持有则自动续期）
     *
     * @param  MallOrder  $order  商城订单
     */
    public function grantOnPaid(MallOrder $order): void
    {
        $order->loadMissing('items.orderable');

        foreach ($order->items as $item) {
            $identity = $item->orderable;
            if (!$identity instanceof Identity) {
                continue;
            }

            $user = $order->user;
            $source = [
                'order_id' => $order->getKey(),
                'order_no' => $order->no,
            ];

            // 已持有且有时效身份 → 续期；否则 → 新授予
            if ($identity->days && $this->has($user, $identity)) {
                $this->renew($user, $identity, $item->qty, IdentityChannel::Subscribe, $source);
            } else {
                $this->entry($user, $identity, IdentityChannel::Subscribe, $item->qty, $source);
            }
        }
    }

    /**
     * 用户是否持有指定身份（含有效期内判断）
     *
     * @param  User  $user  用户
     * @param  Identity  $identity  身份
     *
     * @return bool 是否持有
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

    // ================================================================
    // 付费订阅流程
    // ================================================================

    /**
     * 续期用户身份
     *
     * @param  User  $user  用户
     * @param  Identity  $identity  身份
     * @param  int  $qty  数量
     * @param  IdentityChannel  $channel  变更渠道
     * @param  array  $source  来源信息
     *
     * @throws RuntimeException 用户未持有该身份或身份为永久身份
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

        $this->generateIdentityLog(
            user: $user,
            tenantId: $identity->tenant_id,
            channel: $channel,
            before: $identity,
            after: $identity,
            source: array_merge($source, [
                'action' => 'renew',
                'qty' => $qty,
            ]),
        );
    }

    /**
     * 解析结束时间（限制最大日期为 9999-12-31）
     *
     * @param  Carbon  $endedAT  结束时间
     *
     * @return Carbon 解析后的结束时间
     */
    private function parseEndedAtTime(Carbon $endedAT): Carbon
    {
        $maxDate = Carbon::create(9999, 12, 31, 23, 59, 59);
        if ($endedAT->greaterThan($maxDate)) {
            return $maxDate;
        }

        return $endedAT;
    }

    // ================================================================
    // 续期/延期
    // ================================================================

    /**
     * 用户添加身份
     *
     * @param  User  $user  用户
     * @param  Identity  $identity  身份
     * @param  IdentityChannel  $channel  变更渠道
     * @param  int  $qty  数量
     * @param  array  $source  来源信息
     */
    public function entry(
        User $user,
        Identity $identity,
        IdentityChannel $channel = IdentityChannel::Auto,
        int $qty = 1,
        array $source = []
    ): void {
        $tenantId = $identity->tenant_id;

        // 检查用户是否已有该租户下的身份
        $existingPivot = UserIdentity::where('user_id', $user->getKey())
            ->where('tenant_id', $tenantId)
            ->first();

        $pivot = UserIdentity::where('user_id', $user->getKey())
            ->where('identity_id', $identity->getKey())
            ->first();

        $data['end_at'] = match (true) {
            $pivot && $identity->days => $this->parseEndedAtTime(Carbon::parse($pivot->end_at)->addDays($identity->days * $qty)),
            !$pivot && $identity->days => $this->parseEndedAtTime(Carbon::now()->addDays($identity->days * $qty)),
            default => null
        };

        $data['serial'] = $pivot ? $pivot->serial : UserIdentity::getNewestSerialNo($identity);
        !$pivot && $data['start_at'] = now();
        $data['tenant_id'] = $tenantId;

        $before = null;

        if ($existingPivot) {
            // 已有该租户下的身份，先移除旧身份（只移除当前租户的）
            $before = $existingPivot->identity;
            DB::table('user_identity')
                ->where('user_id', $user->getKey())
                ->where('tenant_id', $tenantId)
                ->delete();
        }

        // 添加新身份
        $user->identities()->attach($identity->getKey(), $data);

        $this->generateIdentityLog(
            user: $user,
            tenantId: $tenantId,
            channel: $channel,
            before: $before,
            after: $identity,
            source: $source,
        );

        IdentityChanged::dispatch(
            user: $user,
            before: $before,
            after: $identity,
            channel: $channel,
        );
    }

    // ================================================================
    // 查询服务层
    // ================================================================

    /**
     * 用户当前所有有效身份
     *
     * @param  User  $user  用户
     *
     * @return Collection<int, Identity> 有效身份列表
     */
    public function activeIdentities(User $user): Collection
    {
        return $user->identities()
            ->where(function (Builder $query) {
                $query->whereNull('user_identity.end_at')
                    ->orWhere('user_identity.end_at', '>', now());
            })
            ->get();
    }

    /**
     * 用户即将过期的身份
     *
     * @param  User  $user  用户
     * @param  int  $days  天数阈值
     *
     * @return Collection<int, Identity> 即将过期的身份列表
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

    /**
     * 检查条件并自动授予满足条件的身份
     *
     * @param  User  $user  用户
     * @param  int  $tenantId  租户ID
     * @param  IdentityChannel  $channel  变更渠道
     */
    public function checkAndAssign(
        User $user,
        int $tenantId,
        IdentityChannel $channel = IdentityChannel::Auto
    ): void {
        $identities = Identity::where('tenant_id', $tenantId)
            ->where('can_subscribe', true)
            ->where('status', true)
            ->whereNotNull('conditions')
            ->get();

        foreach ($identities as $identity) {
            if ($this->has($user, $identity)) {
                continue;
            }

            if ($this->evaluateConditions($user, $identity, $tenantId)) {
                $this->entry($user, $identity, $channel, 1, ['reason' => 'auto_assign']);
            }
        }
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
     *
     * @param  User  $user  用户
     * @param  Identity  $identity  身份
     * @param  int  $tenantId  租户ID
     *
     * @return bool 是否满足条件
     */
    public function evaluateConditions(User $user, Identity $identity, int $tenantId): bool
    {
        $conditions = $identity->conditions;

        if (empty($conditions)) {
            return true;
        }

        if (isset($conditions['min_orders'])) {
            $orderCount = MallOrder::where('user_id', $user->getKey())
                ->where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->count();

            if ($orderCount < $conditions['min_orders']) {
                return false;
            }
        }

        if (isset($conditions['min_amount'])) {
            $totalAmount = MallOrder::where('user_id', $user->getKey())
                ->where('tenant_id', $tenantId)
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
     * 用户移除身份
     *
     * @param  User  $user  用户
     * @param  Identity  $identity  身份
     * @param  IdentityChannel  $channel  变更渠道
     * @param  array  $source  来源信息
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
        $this->generateIdentityLog(
            user: $user,
            tenantId: $identity->tenant_id,
            channel: $channel,
            before: $identity,
            source: $source,
        );
        IdentityChanged::dispatch($user, $identity, null, $channel);
    }
}
