<?php

namespace App\Http\Requests\Mall;

use App\Enums\Mall\RefundReason;
use App\Enums\Mall\RefundType;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class RefundRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                Rule::enum(RefundType::class),
            ],
            'reason' => [
                'required',
                Rule::enum(RefundReason::class),
            ],
            'reason_detail' => [
                'nullable',
                'string',
                'max:500',
            ],
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.order_item_id' => [
                'required',
                'integer',
            ],
            'items.*.qty' => [
                'required',
                'integer',
                'min:1',
            ],
            'items.*.price' => [
                'required',
                'numeric',
                'min:0',
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
            'type.required' => '退款类型必须选择',
            'type.Illuminate\Validation\Rules\Enum' => '退款类型不合法',
            'reason.required' => '退款原因必须选择',
            'reason.Illuminate\Validation\Rules\Enum' => '退款原因不合法',
            'reason_detail.max' => '退款原因详情最长500字符',
            'items.required' => '必须选择退款商品',
            'items.min' => '至少选择一个退款商品',
            'items.*.order_item_id.required' => '退款商品参数必须填写',
            'items.*.order_item_id.integer' => '退款商品参数有误',
            'items.*.qty.required' => '退款数量必须填写',
            'items.*.qty.integer' => '退款数量必须是整数',
            'items.*.qty.min' => '退款数量必须大于0',
            'items.*.price.required' => '退款单价必须填写',
            'items.*.price.numeric' => '退款单价必须是数字',
            'items.*.price.min' => '退款单价不能小于0',
        ];
    }
}
