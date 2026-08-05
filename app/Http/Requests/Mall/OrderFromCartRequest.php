<?php

namespace App\Http\Requests\Mall;

use App\Http\Requests\BaseFormRequest;
use App\Rules\Mall\OrderAddressRule;

class OrderFromCartRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'address_id' => [
                'nullable',
                new OrderAddressRule,
            ],
            'item_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'item_ids.*' => [
                'required',
                'numeric',
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
            'item_ids.required' => '必须选择结算的商品',
            'item_ids.array' => '商品参数有误',
            'item_ids.min' => '至少选择一件商品',
            'item_ids.*.required' => '商品参数有误',
            'item_ids.*.numeric' => '商品参数有误',
        ];
    }
}
