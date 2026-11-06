<?php

namespace App\Http\Requests\Campaign;

use App\Enums\Mall\FulfillmentType;
use App\Http\Requests\BaseFormRequest;
use App\Rules\Mall\OrderAddressRule;
use App\Rules\Mall\PickupPointRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class SeckillOrderRequest extends BaseFormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'qty' => [
                'required',
                'integer',
                'min:1',
                'max:999',
            ],
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
                'numeric',
                Rule::requiredIf(fn () => $this->string('fulfillment_type') === FulfillmentType::Mail->value),
                new OrderAddressRule,
            ],
            'remark' => [
                'nullable',
                'max:255',
            ],
        ];
    }
}
