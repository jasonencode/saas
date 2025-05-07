<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

class LoginRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'username' => [
                'required',
            ],
            'password' => [
                'required',
                Password::min(6),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => '手机号码必须填写',
            'password.required' => '密码必须填写',
        ];
    }
}
