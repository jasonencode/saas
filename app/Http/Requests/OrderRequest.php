<?php

namespace App\Http\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Rules\OrderAddressRule;
use App\Rules\SkuRule;

class OrderRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'address_id' => [
                'nullable',
                new OrderAddressRule(),
            ],
            'items' => [
                'required',
                'array',
            ],
            'items.*.sku_id' => [
                'required',
                'numeric',
                new SkuRule(),
            ],
            'items.*.qty' => [
                'required',
                'numeric',
            ],
            'items.*.remark' => [
                'nullable',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => '必须选择购买的商品',
            'items.array' => '商品参数有误',
            'items.*.sku_id.required' => '商品规格参数必须填写',
            'items.*.sku_id.numeric' => '商品规格参数有误',
            'items.*.qty.required' => '购买数量必须填写',
            'items.*.qty.numeric' => '购买数量必须是数字',
            'items.*.remark.max' => '备注信息最长255字符',
        ];
    }
}