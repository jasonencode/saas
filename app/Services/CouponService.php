<?php

namespace App\Services;

use App\Enums\CouponType;
use App\Enums\ExpiredType;
use App\Models\Coupon;
use App\Models\User;
use InvalidArgumentException;

class CouponService
{
    /**
     * 计算折扣金额
     *
     * @param  Coupon  $coupon
     * @param  float  $totalAmount
     * @return float
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
     * @return void
     */
    public function sendToUser(Coupon $coupon, User $user, int $qty = 1): void
    {
        for ($i = 0; $i < $qty; $i++) {
            $expiredAt = null;

            if ($coupon->expired_type == ExpiredType::Fixed) {
                $expiredAt = $coupon->end_at;
            } elseif ($coupon->expired_type == ExpiredType::Receive && $coupon->days > 0) {
                $expiredAt = now()->addDays($coupon->days);
            }

            $coupon->users()->attach($user->getKey(), [
                'expired_at' => $expiredAt,
            ]);
        }
    }
}
