<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;

class RegisterRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'username' => 'required|string|min:3|max:32|unique:users,username',
            'password' => 'required|string|min:6|max:64|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => '用户名必须填写',
            'username.string' => '用户名格式不正确',
            'username.min' => '用户名至少:min位字符',
            'username.max' => '用户名最多:max位字符',
            'username.unique' => '用户名已被占用',
            'password.required' => '密码必须填写',
            'password.string' => '密码格式不正确',
            'password.min' => '密码至少:min位字符',
            'password.max' => '密码最多:max位字符',
            'password.confirmed' => '两次输入的密码不一致',
        ];
    }
}
