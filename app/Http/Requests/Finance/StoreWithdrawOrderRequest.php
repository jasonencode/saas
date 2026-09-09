<?php

namespace App\Http\Requests\Finance;

use App\Enums\Finance\WithdrawGateway;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StoreWithdrawOrderRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        $gateway = $this->input('gateway');

        $rules = [
            'amount' => 'required|numeric|min:0.01',
            'gateway' => ['required', 'string', Rule::enum(WithdrawGateway::class)],
            'payment_password' => 'required|string|size:6',
            'remark' => 'nullable|string|max:255',
        ];

        // 微信提现无需收款账号：openid 由后端从当前用户微信绑定中解析
        if ($gateway === WithdrawGateway::Wechat->value) {
            $rules['account_info'] = 'nullable|array';
            $rules['account_info.name'] = 'nullable|string|max:64';

            return $rules;
        }

        // 支付宝 / 银行卡提现需填写收款账号
        $rules['account_info'] = 'required|array';
        $rules['account_info.name'] = 'required|string|max:64';
        $rules['account_info.account'] = 'required|string|max:64';
        $rules['account_info.bank'] = 'nullable|string|max:64';
        $rules['account_info.branch'] = 'nullable|string|max:128';

        return $rules;
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => '提现金额必须填写',
            'amount.numeric' => '提现金额格式不正确',
            'amount.min' => '提现金额最小为:min',
            'gateway.required' => '提现方式必须填写',
            'gateway.string' => '提现方式格式不正确',
            'gateway.in' => '提现方式不支持',
            'account_info.required' => '收款账户信息必须填写',
            'account_info.array' => '收款账户信息格式不正确',
            'account_info.name.required' => '收款人姓名必须填写',
            'account_info.name.string' => '收款人姓名格式不正确',
            'account_info.name.max' => '收款人姓名最多:max个字符',
            'account_info.account.required' => '收款账号必须填写',
            'account_info.account.string' => '收款账号格式不正确',
            'account_info.account.max' => '收款账号最多:max个字符',
            'account_info.bank.string' => '银行名称格式不正确',
            'account_info.bank.max' => '银行名称最多:max个字符',
            'account_info.branch.string' => '支行名称格式不正确',
            'account_info.branch.max' => '支行名称最多:max个字符',
            'payment_password.required' => '支付密码必须填写',
            'payment_password.string' => '支付密码格式不正确',
            'payment_password.size' => '支付密码必须为:size位',
            'remark.string' => '备注格式不正确',
            'remark.max' => '备注最多:max个字符',
        ];
    }
}
