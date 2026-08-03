<?php

namespace App\Rules\Mall;

use App\Models\User\Address;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

/**
 * 检查收货地址是否属于当前用户
 *
 * 用法示例：
 * ```
 * 'address_id' => [new OrderAddressRule],
 * * ```
 */
class OrderAddressRule implements ValidationRule
{
    /**
     * 表单数据
     */
    protected array $data;

    /**
     * 验证收货地址归属
     *
     * @param  string  $attribute  验证字段名
     * @param  mixed  $value  地址 ID
     * @param  Closure  $fail  失败回调
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $address = Address::with('user')->find($value);

        if (empty($address)) {
            $fail('地址不存在');

            return;
        }

        if ($address->user->isNot(Auth::user())) {
            $fail('地址不存在');
        }
    }
}
