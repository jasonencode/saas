<?php

namespace App\Services\Campaign;

use App\Contracts\ServiceInterface;
use App\Enums\Campaign\CouponType;
use App\Enums\Campaign\ExpiredType;
use App\Models\Campaign\Coupon;
use App\Models\Campaign\CouponUser;
use App\Models\User\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class CouponService implements ServiceInterface
{
    /**
     * 计算折扣金额
     *
     * @throws InvalidArgumentException
     */
    public function calculateDiscount(Coupon $coupon, float $totalAmount): float
    {
        // 验证优惠券是否有效
        if (!$coupon->isValid()) {
            throw new InvalidArgumentException('优惠券已失效');
        }

        // 验证优惠券是否可用
        if (!$coupon->canBeUsed()) {
            throw new InvalidArgumentException('优惠券不可用');
        }

        // 验证订单金额是否满足使用条件
        if ($coupon->min_amount && $totalAmount < $coupon->min_amount) {
            throw new InvalidArgumentException(
                sprintf('订单金额未满足使用条件，最低需要 ￥%s', number_format($coupon->min_amount, 2))
            );
        }

        // 根据优惠券类型计算折扣金额
        if ($coupon->type === CouponType::Fixed) {
            return min($coupon->value, $totalAmount);
        }

        if ($coupon->type === CouponType::Percent) {
            $discount = $totalAmount * ($coupon->value / 100);

            return $coupon->max_discount ? min($discount, $coupon->max_discount) : $discount;
        }

        return 0;
    }

    /**
     * 发送优惠券
     *
     * @param  Coupon  $coupon  优惠券
     * @param  User  $user  领取用户
     * @param  int  $qty  发送数量
     *
     * @throws InvalidArgumentException|Throwable 优惠券已达发放上限时抛出
     */
    public function sendToUser(Coupon $coupon, User $user, int $qty = 1): void
    {
        // 检查优惠券自身是否可发放，用户限领在下方返回更具体的错误信息。
        if (!$coupon->isValid()) {
            throw new InvalidArgumentException('优惠券已失效');
        }

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
                CouponUser::query()->create([
                    'coupon_id' => $coupon->getKey(),
                    'user_id' => $user->getKey(),
                    'expired_at' => $expiredAt,
                ]);
            }
        });
    }
}
