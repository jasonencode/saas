<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;

class MiniProgramLoginRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => '手机号授权凭证不能为空',
        ];
    }
}
