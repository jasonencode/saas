<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rules\Password;

class PasswordLoginRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
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
            'captcha_key' => 'required',
            'captcha_code' => [
                'required',
                'captcha_api:'.request('captcha_key'),
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
            'username.required' => '手机号码必须填写',
            'password.required' => '密码必须填写',
            'captcha_key.required' => '验证码密钥必须填写',
            'captcha_code.required' => '验证码必须填写',
            'captcha_code.captcha_api' => '验证码不正确',
        ];
    }
}
