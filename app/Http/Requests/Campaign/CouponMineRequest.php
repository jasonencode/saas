<?php

namespace App\Http\Requests\Campaign;

use App\Http\Requests\BaseFormRequest;

class CouponMineRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'is_used' => ['sometimes', 'boolean'],
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
            'is_used.boolean' => '使用状态参数不正确',
            'limit.integer' => '每页数量必须是整数',
            'limit.min' => '每页数量最少为1',
            'limit.max' => '每页数量最多为100',
        ];
    }
}
