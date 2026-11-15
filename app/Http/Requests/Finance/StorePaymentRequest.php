<?php

namespace App\Http\Requests\Finance;

use App\Enums\Finance\PaymentGateway;
use App\Http\Requests\BaseFormRequest;
use App\Services\Finance\PaymentableResolver;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'amount' => 'nullable|numeric|min:0.01',
            'gateway' => ['required', 'string', Rule::in([
                PaymentGateway::Wechat->value,
                PaymentGateway::Balance->value,
            ])],
            'paymentable_type' => ['required', 'string', Rule::in(PaymentableResolver::keys())],
            'paymentable_id' => 'required|integer',
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
            'amount.numeric' => '支付金额格式不正确',
            'amount.min' => '支付金额最小为:min',
            'gateway.required' => '支付网关必须填写',
            'gateway.string' => '支付网关格式不正确',
            'gateway.in' => '支付网关不支持',
            'paymentable_type.required' => '支付关联类型必须填写',
            'paymentable_type.string' => '关联类型格式不正确',
            'paymentable_type.in' => '支付关联类型不支持',
            'paymentable_id.required' => '支付关联ID必须填写',
            'paymentable_id.integer' => '关联ID格式不正确',
        ];
    }
}
