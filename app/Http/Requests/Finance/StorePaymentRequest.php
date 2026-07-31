<?php

namespace App\Http\Requests\Finance;

use App\Enums\Finance\PaymentGateway;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'gateway' => ['required', 'string', Rule::enum(PaymentGateway::class)],
            'paymentable_type' => 'nullable|string',
            'paymentable_id' => 'nullable|integer',
            'remark' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => '支付金额必须填写',
            'amount.numeric' => '支付金额格式不正确',
            'amount.min' => '支付金额最小为:min',
            'gateway.required' => '支付网关必须填写',
            'gateway.string' => '支付网关格式不正确',
            'paymentable_type.string' => '关联类型格式不正确',
            'paymentable_id.integer' => '关联ID格式不正确',
            'remark.string' => '备注格式不正确',
            'remark.max' => '备注最多:max位字符',
        ];
    }
}
