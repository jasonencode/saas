<?php

namespace App\Http\Requests\Mall;

use App\Http\Requests\BaseFormRequest;

class CheckoutPreviewRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'item_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'item_ids.*' => [
                'required',
                'numeric',
            ],
            'address_id' => [
                'nullable',
                'numeric',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'item_ids.required' => '必须选择结算的商品',
            'item_ids.array' => '商品参数有误',
            'item_ids.min' => '至少选择一件商品',
        ];
    }
}
