<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Password;

class SetPaymentPasswordRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        $rules = [
            'password' => [
                'required',
                Password::min(6)->max(20),
            ],
            're_password' => [
                'required',
                'same:password',
            ],
        ];

        if ($this->routeIs('user.safe.payment-password.change')) {
            $rules['old_password'] = 'required|min:6';
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
            'password.required' => '新密码必须填写',
            'password.min' => '新密码至少:min 位字符',
            'password.max' => '新密码最多:max 位字符',
            're_password.required' => '确认密码必须填写',
            're_password.same' => '两次输入的密码不一致',
            'old_password.required' => '原密码必须填写',
            'old_password.min' => '原密码至少:min 位字符',
        ];
    }
}
