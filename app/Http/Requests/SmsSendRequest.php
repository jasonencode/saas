<?php

namespace App\Http\Requests;

use App\Rules\MobileRule;

class SmsSendRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                new MobileRule(),
            ],
            'captcha_key' => 'required',
            'captcha_code' => [
                'required',
                'captcha_api:'.request('captcha_key'),
            ],
        ];
    }
}
