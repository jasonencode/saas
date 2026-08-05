<?php

namespace App\Http\Requests\Mall;

use App\Http\Requests\BaseFormRequest;

class UpdateCartItemRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
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

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
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
