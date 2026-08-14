<?php

namespace App\Http\Requests\Mall;

use App\Enums\Mall\FulfillmentType;
use App\Http\Requests\BaseFormRequest;
use App\Rules\Mall\OrderAddressRule;
use App\Rules\Mall\PickupPointRule;
use App\Rules\Mall\SkuRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class OrderRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|Enum|Rule>>
     */
    public function rules(): array
    {
        return [
            'fulfillment_type' => [
                'required',
                new Enum(FulfillmentType::class),
            ],
            'pickup_point_id' => [
                'nullable',
                'numeric',
                Rule::requiredIf($this->safe()->string('fulfillment_type') === FulfillmentType::Pickup->value),
                new PickupPointRule,
            ],
            'address_id' => [
                'nullable',
                new OrderAddressRule,
            ],
            'items' => [
                'required',
                'array',
            ],
            'items.*.product_sku_id' => [
                'required',
                'numeric',
                new SkuRule,
            ],
            'items.*.qty' => [
                'required',
                'numeric',
            ],
            'items.*.remark' => [
                'nullable',
                'max:255',
            ],
            'remark' => [
                'nullable',
                'string',
                'max:255',
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
            'items.required' => '必须选择购买的商品',
            'items.array' => '商品参数有误',
            'items.*.product_sku_id.required' => '商品规格参数必须填写',
            'items.*.product_sku_id.numeric' => '商品规格参数有误',
            'items.*.qty.required' => '购买数量必须填写',
            'items.*.qty.numeric' => '购买数量必须是数字',
            'items.*.remark.max' => '备注信息最长255字符',
        ];
    }
}
