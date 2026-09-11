<?php

namespace App\Http\Requests\Campaign;

use App\Http\Requests\BaseFormRequest;

class CouponAvailableRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.sku_id' => [
                'required',
                'numeric',
            ],
            'items.*.qty' => [
                'required',
                'numeric',
                'min:1',
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
            'items.required' => '必须提供结算商品',
            'items.array' => '商品参数有误',
            'items.min' => '至少提供一件商品',
            'items.*.sku_id.required' => '商品参数有误',
            'items.*.sku_id.numeric' => '商品参数有误',
            'items.*.qty.required' => '商品数量必须填写',
            'items.*.qty.numeric' => '商品数量必须是数字',
            'items.*.qty.min' => '商品数量不能少于1',
        ];
    }
}
