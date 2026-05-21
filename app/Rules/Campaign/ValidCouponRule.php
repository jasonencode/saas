<?php

namespace App\Rules\Campaign;

use App\Models\Campaign\Coupon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 检查优惠券是否可用
 *
 * 验证：存在、启用、在有效期内、未超出使用限制。
 *
 * 用法示例：
 * ```
 * 'coupon_id' => [new ValidCouponRule],
 * ```
 */
class ValidCouponRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return; // 优惠券可选，空值跳过验证
        }

        $coupon = Coupon::where('status', true)->find($value);

        if (! $coupon) {
            $fail('优惠券不存在或已失效');

            return;
        }

        if (! $coupon->isValid()) {
            $fail('优惠券已过期或尚未生效');
        }
    }
}
