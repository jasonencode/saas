<?php

namespace App\Http\Requests\Mall;

use App\Http\Requests\BaseFormRequest;

class UpdateCartItemRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'qty' => [
                'required',
                'integer',
                'min:1',
                'max:9999',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'qty.required' => '请输入数量',
            'qty.integer' => '数量必须是整数',
            'qty.min' => '数量不能少于1',
            'qty.max' => '数量不能超过9999',
        ];
    }
}
