<?php

namespace App\Rules\Mall;

use App\Models\Mall\PickupPoint;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 检查自提点是否存在且启用
 *
 * 用法示例：
 * ```
 * 'pickup_point_id' => [new PickupPointRule],
 * ```
 */
class PickupPointRule implements ValidationRule
{
    /**
     * 验证自提点是否存在且启用
     *
     * @param  string  $attribute  验证字段名
     * @param  mixed  $value  自提点 ID
     * @param  Closure  $fail  失败回调
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $point = PickupPoint::find($value);

        if (empty($point)) {
            $fail('自提点不存在');

            return;
        }

        if (!$point->status) {
            $fail('自提点已停用');
        }
    }
}
