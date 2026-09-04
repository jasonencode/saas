<?php

namespace App\Http\Requests\Mall;

use App\Enums\Mall\FulfillmentType;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Enum;

class CheckoutPreviewRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|Enum>>
     */
    public function rules(): array
    {
        return [
            'fulfillment_type' => [
                'required',
                new Enum(FulfillmentType::class),
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
            'address_id' => [
                'nullable',
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
            'fulfillment_type.required' => '配送方式必须选择',
            'fulfillment_type.Illuminate\Validation\Rules\Enum' => '配送方式不正确',
            'item_ids.required' => '必须选择结算的商品',
            'item_ids.array' => '商品参数有误',
            'item_ids.min' => '至少选择一件商品',
            'item_ids.*.required' => '商品参数有误',
            'item_ids.*.numeric' => '商品参数有误',
            'address_id.numeric' => '收货地址参数不正确',
        ];
    }
}
