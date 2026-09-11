<?php

namespace App\Services\Campaign;

use App\Contracts\ServiceInterface;
use App\Enums\Campaign\CouponType;
use App\Enums\Campaign\ExpiredType;
use App\Models\Campaign\Coupon;
use App\Models\Campaign\CouponUser;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Models\Mall\Sku;
use App\Models\User\User;
use App\Services\Mall\DTOs\OrderItemDto;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class CouponService implements ServiceInterface
{
    /**
     * 计算折扣金额（bcmath，保留 2 位）
     *
     * @param  Coupon  $coupon  优惠券
     * @param  string  $baseAmount  抵扣基数（适用商品小计）
     *
     * @throws InvalidArgumentException 券失效、不可用或金额不满足条件
     *
     * @return string 折扣金额
     */
    public function calculateDiscount(Coupon $coupon, string $baseAmount): string
    {
        if (!$coupon->isValid()) {
            throw new InvalidArgumentException('优惠券已失效');
        }

        if (!$coupon->canBeUsed()) {
            throw new InvalidArgumentException('优惠券不可用');
        }

        if ($coupon->min_amount && bccomp($baseAmount, (string) $coupon->min_amount, 2) === -1) {
            throw new InvalidArgumentException(
                sprintf('订单金额未满足使用条件，最低需要 ￥%s', number_format((float) $coupon->min_amount, 2))
            );
        }

        return match ($coupon->type) {
            // 固定金额：抵扣不超过基数（实付不为负）；面额归一化到 2 位（decimal 字段在部分驱动下为数值）
            CouponType::Fixed => bccomp((string) $coupon->value, $baseAmount, 2) === 1
                ? $baseAmount
                : number_format((float) $coupon->value, 2, '.', ''),
            // 百分比：基数 × value ÷ 100，受 max_discount 封顶
            CouponType::Percent => $this->capPercentDiscount($baseAmount, $coupon),
        };
    }

    /**
     * 百分比折扣：基数 × value ÷ 100，受 max_discount 封顶。
     * 舍入口径与 ProductDiscountService::applyPercent 一致：
     * 先乘后除、保留 4 位中间值，再四舍五入到分（number_format），
     * 避免 bcdiv 直接截断（33.33 × 15% 应为 5.00 而非 4.99）。
     *
     * @param  string  $baseAmount  抵扣基数
     * @param  Coupon  $coupon  优惠券
     *
     * @return string 折扣金额（保留两位小数）
     */
    private function capPercentDiscount(string $baseAmount, Coupon $coupon): string
    {
        $raw = bcdiv(bcmul($baseAmount, (string) $coupon->value, 6), '100', 4);
        $discount = number_format((float) $raw, 2, '.', '');

        if ($coupon->max_discount && bccomp($discount, (string) $coupon->max_discount, 2) === 1) {
            $discount = number_format((float) $coupon->max_discount, 2, '.', '');
        }

        return $discount;
    }

    /**
     * 计算券对一组订单项的抵扣基数
     *
     * coupon_product 为空 → 全场适用（该租户），基数 = 订单项总额；
     * 非空 → 基数 = 订单项中命中适用商品的小计；未命中任何商品返回 null（券不可用）。
     *
     * @param  Coupon  $coupon  优惠券
     * @param  Collection<int, OrderItemDto>  $items  订单项（同租户）
     *
     * @return string|null 抵扣基数，券不适用时 null
     */
    public function baseAmountFor(Coupon $coupon, Collection $items): ?string
    {
        // 一次查询取适用商品 ID 集合（空集合 = 全场）
        $productIds = $coupon->products()->pluck('products.id');

        $base = '0.00';
        $hit = $productIds->isEmpty();

        foreach ($items as $item) {
            // 仅实体商品参与商品范围匹配
            if (!$item->orderable instanceof Sku) {
                continue;
            }

            if (!$productIds->isEmpty() && !$productIds->contains($item->orderable->product_id)) {
                continue;
            }

            $hit = true;
            $base = bcadd($base, $item->getAmount(), 2);
        }

        return $hit ? $base : null;
    }

    /**
     * 预估券对一组订单项的抵扣金额（预览用，不落库）
     *
     * @param  CouponUser  $couponUser  用户持券实例
     * @param  User  $user  当前用户
     * @param  Collection<int, OrderItemDto>  $items  订单项（同租户）
     *
     * @throws InvalidArgumentException 券不可用（校验见 applyToOrder）
     *
     * @return array{base_amount: string, discount: string} 基数与抵扣金额
     */
    public function previewDiscount(CouponUser $couponUser, User $user, Collection $items): array
    {
        $discount = $this->validateAndCalculate($couponUser, $user, $items);

        return [
            'base_amount' => $this->baseAmountFor($couponUser->coupon, $items),
            'discount' => $discount,
        ];
    }

    /**
     * 核销券到订单（在 OrderService::createOrder 事务内调用）
     *
     * 校验（按序）：
     *  1. 券实例归属当前用户（coupon_user.user_id）
     *  2. 券未使用（is_used = false）
     *  3. 券实例未过期（expired_at 为空或晚于当前）
     *  4. 券定义 isValid()
     *  5. 券租户 = 订单租户
     *  6. baseAmountFor 非空（适用商品范围）
     *  7. 内部计算抵扣金额（含 min_amount 校验与 0.01 clamp，与 previewDiscount 同路径）
     *
     * 写入：
     *  - coupon_user：乐观核销，affected=0 → 抛「优惠券已被使用」回滚
     *  - orders.coupon_discount = 抵扣金额
     *  - order_items.coupon_discount = 各订单项分摊金额（快照，供展示与退款分摊读取）
     *  - coupon_order：(order_id, coupon_id, coupon_user_id, discount_amount)
     *
     * @param  CouponUser  $couponUser  用户持券实例
     * @param  Order  $order  订单
     * @param  Collection<int, OrderItemDto>  $items  该订单的订单项（同租户）
     *
     * @throws InvalidArgumentException 券不可用或已被使用
     *
     * @return string 实际抵扣金额
     */
    public function applyToOrder(CouponUser $couponUser, Order $order, Collection $items): string
    {
        $discount = $this->validateAndCalculate($couponUser, $order->user, $items, $order->tenant_id);

        // 乐观核销：并发下仅一笔成功，affected=0 视为已被使用
        $affected = CouponUser::query()
            ->whereKey($couponUser->getKey())
            ->where('is_used', false)
            ->update(['is_used' => true, 'used_at' => now()]);

        if ($affected === 0) {
            throw new InvalidArgumentException('优惠券已被使用');
        }

        $order->coupon_discount = $discount;
        $order->save();

        // 逐项记录分摊金额：订单详情逐项展示、退款按快照分摊（不再重算）
        foreach ($this->apportionDiscount($discount, $order->items()->get()) as $itemId => $share) {
            $order->items()->whereKey($itemId)->update(['coupon_discount' => $share]);
        }

        $order->coupons()->attach($couponUser->coupon_id, [
            'coupon_user_id' => $couponUser->getKey(),
            'discount_amount' => $discount,
        ]);

        return $discount;
    }

    /**
     * 按订单项小计比例分摊抵扣金额
     *
     * 分摊比例 = 订单项小计 ÷ 订单项小计合计；每项四舍五入到分后，
     * 累积尾差归位到金额最大的订单项，保证 Σ分摊 = 抵扣金额。
     *
     * @param  string  $discount  待分摊的抵扣金额
     * @param  Collection<int, OrderItem>  $items  订单项（需含 price / qty）
     *
     * @return array<int, string> 订单项 ID => 分摊金额（无需分摊时为空数组）
     */
    public function apportionDiscount(string $discount, Collection $items): array
    {
        $total = $items->reduce(
            fn (string $carry, OrderItem $item) => bcadd($carry, bcmul((string) $item->price, (string) $item->qty, 2), 2),
            '0.00'
        );

        // 无抵扣或商品总额为 0：无分摊
        if (bccomp($discount, '0', 2) !== 1 || bccomp($total, '0', 2) !== 1) {
            return [];
        }

        $shares = [];
        $allocated = '0.00';
        $largestItemId = null;
        $largestSubtotal = '-1';

        foreach ($items as $item) {
            $subtotal = bcmul((string) $item->price, (string) $item->qty, 2);

            if (bccomp($subtotal, $largestSubtotal, 2) === 1) {
                $largestSubtotal = $subtotal;
                $largestItemId = $item->getKey();
            }

            // 抵扣金额 × (项小计 ÷ 小计合计)：先算 4 位再四舍五入到分
            $raw = bcdiv(bcmul($discount, $subtotal, 6), $total, 4);
            $share = number_format((float) $raw, 2, '.', '');

            $shares[$item->getKey()] = $share;
            $allocated = bcadd($allocated, $share, 2);
        }

        $diff = bcsub($discount, $allocated, 2);

        if ($largestItemId !== null && bccomp($diff, '0', 2) !== 0) {
            $shares[$largestItemId] = bcadd($shares[$largestItemId], $diff, 2);
        }

        return $shares;
    }

    /**
     * 释放订单占用的券（未支付取消时调用）
     *
     * 幂等：无 coupon_order 记录则 no-op。
     * 写入：删除 coupon_order、orders.coupon_discount 归零、
     *       coupon_user.is_used=false / used_at=null
     *
     * 注意：返还时券实例若已过期，仍恢复 is_used=false（过期态由 expired_at 表达，
     * 用户侧自然显示为过期券，不重复占用发放额度）。
     *
     * @param  Order  $order  订单
     */
    public function releaseFromOrder(Order $order): void
    {
        $this->doRelease($order, resetDiscount: true);
    }

    /**
     * 释放已全额退款订单占用的券
     *
     * 与取消释放的区别：退款已完成，订单实付金额是既成事实（退款金额按实付口径分摊），
     * 故**保留** orders.coupon_discount 快照，仅删除用券记录并恢复券实例，
     * 避免订单历史实付口径被改写（开票等按 total_amount 计算的场景依赖该口径）。
     *
     * @param  Order  $order  订单
     */
    public function releaseFromRefundedOrder(Order $order): void
    {
        $this->doRelease($order, resetDiscount: false);
    }

    /**
     * 释放执行：删除用券记录并恢复券实例
     *
     * @param  Order  $order  订单
     * @param  bool  $resetDiscount  是否同时将 orders.coupon_discount 归零
     */
    private function doRelease(Order $order, bool $resetDiscount): void
    {
        $couponUser = CouponUser::query()
            ->whereIn('id', $order->coupons()->pluck('coupon_user_id'))
            ->first();

        if (!$couponUser) {
            return;
        }

        $order->coupons()->detach();

        if ($resetDiscount) {
            $order->coupon_discount = 0;
            $order->save();
        }

        CouponUser::query()
            ->whereKey($couponUser->getKey())
            ->update(['is_used' => false, 'used_at' => null]);
    }

    /**
     * 核销校验与抵扣计算（previewDiscount / applyToOrder 共用同一路径，保证金额一致）
     *
     * @param  CouponUser  $couponUser  用户持券实例
     * @param  User  $user  当前用户
     * @param  Collection<int, OrderItemDto>  $items  订单项（同租户）
     * @param  int|null  $orderTenantId  订单租户 ID（为空时取订单项租户）
     *
     * @throws InvalidArgumentException 券不可用
     *
     * @return string 抵扣金额（含 min_amount 校验与最低实付 clamp）
     */
    private function validateAndCalculate(CouponUser $couponUser, User $user, Collection $items, ?int $orderTenantId = null): string
    {
        if ($items->isEmpty()) {
            throw new InvalidArgumentException('优惠券不适用于所选商品');
        }

        // 1. 券实例归属当前用户
        if ((int) $couponUser->user_id !== (int) $user->getKey()) {
            throw new InvalidArgumentException('优惠券不属于当前用户');
        }

        // 2. 券未使用
        if ($couponUser->is_used) {
            throw new InvalidArgumentException('优惠券已被使用');
        }

        // 3. 券实例未过期
        if ($couponUser->expired_at !== null && $couponUser->expired_at->isPast()) {
            throw new InvalidArgumentException('优惠券已过期');
        }

        $coupon = $couponUser->coupon;

        // 4. 券定义有效（status + 有效期内）
        if (!$coupon || !$coupon->isValid()) {
            throw new InvalidArgumentException('优惠券已失效');
        }

        // 5. 券租户 = 订单租户
        $itemsTenantId = $orderTenantId ?? $items->first()->tenantId;
        if ((int) $coupon->tenant_id !== (int) $itemsTenantId) {
            throw new InvalidArgumentException('优惠券不适用于所选商品');
        }

        // 6. 适用商品范围
        $baseAmount = $this->baseAmountFor($coupon, $items);
        if ($baseAmount === null) {
            throw new InvalidArgumentException('优惠券不适用于所选商品');
        }

        // 7. 抵扣计算（内层：抵扣 ≤ 基数；外层：订单商品实付 ≥ 0.01）
        return $this->calculateWithClamp($coupon, $baseAmount, $items);
    }

    /**
     * 抵扣计算：内层 calculateDiscount（抵扣 ≤ 基数）+ 外层最低实付 clamp
     *
     * 券不作用于运费，clamp 商品实付最低 0.01：coupon_discount ≤ amount - 0.01
     * $orderAmount = 全部订单项小计（含不适用商品，区别于 base_amount）
     *
     * @param  Coupon  $coupon  优惠券
     * @param  string  $baseAmount  抵扣基数（适用商品小计）
     * @param  Collection<int, OrderItemDto>  $items  订单项（同租户）
     *
     * @return string 抵扣金额
     */
    private function calculateWithClamp(Coupon $coupon, string $baseAmount, Collection $items): string
    {
        $discount = $this->calculateDiscount($coupon, $baseAmount);

        // 订单商品总额（含不适用商品）
        $orderAmount = $items->reduce(
            fn (string $carry, OrderItemDto $item) => bcadd($carry, $item->getAmount(), 2),
            '0.00'
        );

        // 金额比较统一用 bccomp，不用 min() 做字符串数值比较
        if (bccomp($discount, bcsub($orderAmount, '0.01', 2), 2) === 1) {
            $discount = bcsub($orderAmount, '0.01', 2);
        }

        return $discount;
    }

    /**
     * 发送优惠券
     *
     * @param  Coupon  $coupon  优惠券
     * @param  User  $user  领取用户
     * @param  int  $qty  发送数量
     *
     * @throws InvalidArgumentException|Throwable 优惠券已达发放上限或领取超时
     */
    public function sendToUser(Coupon $coupon, User $user, int $qty = 1): void
    {
        // 检查优惠券自身是否可发放，用户限领在下方返回更具体的错误信息。
        if (!$coupon->isValid()) {
            throw new InvalidArgumentException('优惠券已失效');
        }

        // 阻塞式锁防并发超发（领券场景短暂等待体验优于直接拒绝）
        $lock = Cache::lock("coupon_send_{$coupon->getKey()}", 10);

        try {
            $lock->block(5, function () use ($coupon, $user, $qty) {
                $this->doSendToUser($coupon, $user, $qty);
            });
        } catch (LockTimeoutException) {
            throw new InvalidArgumentException('当前领取人数较多，请稍后再试');
        }
    }

    /**
     * 发放执行（锁内调用：count 校验 → 插入）
     *
     * @param  Coupon  $coupon  优惠券
     * @param  User  $user  领取用户
     * @param  int  $qty  发送数量
     *
     * @throws InvalidArgumentException 优惠券已达发放上限
     * @throws Throwable
     */
    private function doSendToUser(Coupon $coupon, User $user, int $qty): void
    {
        // 精确检查此次发放是否会突破总限额
        if ($coupon->usage_limit !== null) {
            $issuedCount = $coupon->users()->count();
            $remaining = $coupon->usage_limit - $issuedCount;

            if ($remaining <= 0) {
                throw new InvalidArgumentException('优惠券发放已达上限');
            }

            if ($qty > $remaining) {
                throw new InvalidArgumentException("优惠券剩余可发放数量不足，仅剩 $remaining 张");
            }
        }

        // 精确检查此次发放是否会突破用户每人限领
        if ($coupon->usage_limit_per_user !== null) {
            $userCount = $coupon->users()
                ->wherePivot('user_id', $user->getKey())
                ->count();
            $userRemaining = $coupon->usage_limit_per_user - $userCount;

            if ($userRemaining <= 0) {
                throw new InvalidArgumentException('您已领取过该优惠券，不可重复领取');
            }

            if ($qty > $userRemaining) {
                throw new InvalidArgumentException("您最多还可领取 $userRemaining 张");
            }
        }

        // 计算过期时间
        $expiredAt = match ($coupon->expired_type) {
            ExpiredType::Fixed => $coupon->end_at,
            ExpiredType::Receive => $coupon->days > 0 ? now()->addDays($coupon->days) : null,
            default => null,
        };

        // 事务内批量发放
        DB::transaction(static function () use ($coupon, $user, $qty, $expiredAt) {
            for ($i = 0; $i < $qty; $i++) {
                CouponUser::create([
                    'coupon_id' => $coupon->getKey(),
                    'user_id' => $user->getKey(),
                    'expired_at' => $expiredAt,
                ]);
            }
        });
    }
}
