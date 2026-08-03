<?php

namespace App\Rules\Mall;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * 检查订单是否符合取消条件
 *
 * 只有「待付款」状态的订单可以取消。
 *
 * 用法示例：
 * ```
 * 'order_no' => [new OrderCancellableRule],
 * ```
 */
class OrderCancellableRule implements ValidationRule
{
    /**
     * 验证订单是否可取消
     *
     * @param  string  $attribute  验证字段名
     * @param  mixed  $value  订单号
     * @param  Closure  $fail  失败回调
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $order = Order::where('no', $value)->first();

        if (!$order) {
            $fail('订单不存在');

            return;
        }

        if ($order->status !== OrderStatus::Pending) {
            $fail('当前订单状态不允许取消');
        }
    }
}
