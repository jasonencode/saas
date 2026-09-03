<?php

namespace App\Http\Requests\Finance;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\RechargeOrderType;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StoreRechargeOrderRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'type' => ['required', 'string', Rule::enum(RechargeOrderType::class)],
            'gateway' => ['required', 'string', Rule::enum(PaymentGateway::class)],
            'remark' => 'nullable|string|max:255',
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
            'amount.required' => '充值金额必须填写',
            'amount.numeric' => '充值金额格式不正确',
            'amount.min' => '充值金额最小为:min',
            'type.required' => '充值类型必须填写',
            'type.string' => '充值类型格式不正确',
            'type.in' => '充值类型不支持',
            'gateway.required' => '支付网关必须填写',
            'gateway.string' => '支付网关格式不正确',
            'gateway.in' => '支付网关不支持',
            'remark.string' => '备注格式不正确',
            'remark.max' => '备注最多:limit个字符',
        ];
    }
}
