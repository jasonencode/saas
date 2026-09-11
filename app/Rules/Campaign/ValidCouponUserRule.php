<?php

namespace App\Rules\Campaign;

use App\Models\Campaign\CouponUser;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * 用户持券实例校验规则
 *
 * 校验 coupon_user_id：存在、归属当前用户、未使用、未过期。
 * 供下单/预览请求的 coupon_user_id 字段使用。
 *
 * 用法示例：
 * ```
 * 'coupon_user_id' => ['nullable', new ValidCouponUserRule],
 * ```
 */
class ValidCouponUserRule implements ValidationRule
{
    /**
     * 运行校验规则
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $couponUser = CouponUser::query()->find($value);

        if (!$couponUser) {
            $fail('优惠券不存在');

            return;
        }

        if ((int) $couponUser->user_id !== (int) auth()->id()) {
            $fail('优惠券不属于当前用户');

            return;
        }

        if ($couponUser->is_used) {
            $fail('优惠券已被使用');

            return;
        }

        if ($couponUser->expired_at !== null && $couponUser->expired_at->isPast()) {
            $fail('优惠券已过期');

            return;
        }
    }
}
