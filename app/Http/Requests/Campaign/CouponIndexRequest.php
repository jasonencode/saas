<?php

namespace App\Http\Requests\Campaign;

use App\Enums\Campaign\CouponType;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class CouponIndexRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::enum(CouponType::class)],
            'min_amount' => ['sometimes', 'numeric', 'min:0'],
            'max_amount' => ['sometimes', 'numeric', 'min:0'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.Illuminate\Validation\Rules\Enum' => '优惠券类型不正确',
            'min_amount.numeric' => '最低金额必须是数字',
            'min_amount.min' => '最低金额不能小于:min',
            'max_amount.numeric' => '最高金额必须是数字',
            'max_amount.min' => '最高金额不能小于:min',
            'limit.integer' => '每页数量必须是整数',
            'limit.min' => '每页数量最少为1',
            'limit.max' => '每页数量最多为100',
        ];
    }
}
