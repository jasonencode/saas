<?php

namespace App\Http\Requests;

use App\Rules\MobileRule;

class SmsSendRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'mobile' => [
                'required',
                new MobileRule,
            ],
            'captcha_key' => 'required',
            'captcha_code' => [
                'required',
                'captcha_api:'.request('captcha_key'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.required' => '手机号必须填写',
            'captcha_key.required' => '验证码标识必须填写',
            'captcha_code.required' => '验证码必须填写',
            'captcha_code.captcha_api' => '验证码不正确',
        ];
    }
}
