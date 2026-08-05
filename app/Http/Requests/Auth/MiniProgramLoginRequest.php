<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;

class MiniProgramLoginRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
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
            'code.required' => '手机号授权凭证不能为空',
        ];
    }
}
