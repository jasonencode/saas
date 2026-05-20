<?php

namespace App\Http\Requests\Mall;

use App\Http\Requests\BaseFormRequest;
use App\Models\Mall\Sku;
use Illuminate\Validation\Rule;

class StoreCartItemRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'sku_id' => [
                'required',
                'integer',
                Rule::exists(Sku::class, 'id'),
            ],
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
            'sku_id.required' => '请选择商品规格',
            'sku_id.exists' => '商品规格不存在',
            'qty.required' => '请输入购买数量',
            'qty.integer' => '购买数量必须是整数',
            'qty.min' => '购买数量不能少于1',
            'qty.max' => '单次购买数量不能超过9999',
        ];
    }
}
