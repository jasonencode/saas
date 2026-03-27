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
        ];
    }
}
