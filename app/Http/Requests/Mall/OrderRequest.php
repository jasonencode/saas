<?php

namespace App\Http\Requests\Mall;

use App\Enums\Mall\FulfillmentType;
use App\Http\Requests\BaseFormRequest;
use App\Rules\Mall\OrderableRule;
use App\Rules\Mall\OrderAddressRule;
use App\Rules\Mall\PickupPointRule;
use App\Services\Mall\OrderableResolver;
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
                Rule::requiredIf(fn () => $this->string('fulfillment_type') === FulfillmentType::Pickup->value),
                new PickupPointRule,
            ],
            'address_id' => [
                'nullable',
                Rule::requiredIf(fn () => $this->string('fulfillment_type') === FulfillmentType::Mail->value),
                new OrderAddressRule,
            ],
            'orderable_type' => [
                'required',
                'string',
                Rule::in(OrderableResolver::keys()),
            ],
            'orderable_id' => [
                'required',
                'numeric',
                new OrderableRule,
            ],
            'qty' => [
                'required',
                'numeric',
                'min:1',
            ],
            'remark' => [
                'nullable',
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
            'orderable_type.required' => '商品类型必须填写',
            'orderable_type.in' => '商品类型参数有误',
            'orderable_id.required' => '商品参数必须填写',
            'orderable_id.numeric' => '商品参数有误',
            'qty.required' => '购买数量必须填写',
            'qty.numeric' => '购买数量必须是数字',
            'qty.min' => '购买数量不能少于1',
            'remark.max' => '备注信息最长255字符',
        ];
    }
}
