<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;
use App\Rules\PaymentPasswordRule;

class SetPaymentPasswordRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string|PaymentPasswordRule>>
     */
    public function rules(): array
    {
        $rules = [
            'password' => ['required', new PaymentPasswordRule],
            're_password' => [
                'required',
                'same:password',
            ],
        ];

        if ($this->routeIs('user.safe.payment-password.change')) {
            $rules['old_password'] = ['required', new PaymentPasswordRule];
        }

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
            'password.required' => '支付密码必须填写',
            're_password.required' => '确认密码必须填写',
            're_password.same' => '两次输入的密码不一致',
            'old_password.required' => '原支付密码必须填写',
        ];
    }
}
