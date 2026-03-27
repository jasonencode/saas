<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'old_pass' => 'required|min:6|current_password',
            'new_pass' => [
                'required',
                Password::min(6)->max(20),
            ],
            're_pass' => [
                'required',
                'same:new_pass',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'old_pass.required' => '原密码必须填写',
            'old_pass.min' => '原密码至少:min 位字符',
            'old_pass.current_password' => '原密码不正确',
            'new_pass.required' => '新密码必须填写',
            'new_pass.min' => '新密码至少:min 位字符',
            'new_pass.max' => '新密码最多:max 位字符',
            're_pass.required' => '确认密码必须填写',
            're_pass.same' => '两次输入的密码不一致',
        ];
    }
}
