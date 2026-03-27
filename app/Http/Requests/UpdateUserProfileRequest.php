<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Rules\FileExistsRule;
use Illuminate\Validation\Rule;

class UpdateUserProfileRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'nickname' => [
                'required',
                'string',
                'min:2,max:32',
            ],
            'gender' => [
                'sometimes',
                'nullable',
                Rule::enum(Gender::class),
            ],
            'birthday' => [
                'sometimes',
                'nullable',
                'date',
            ],
            'avatar' => [
                'sometimes',
                'nullable',
                'string',
                new FileExistsRule('头像文件不存在'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nickname.required' => '昵称必须填写',
            'nickname.string' => '昵称必须是字符串',
            'nickname.min' => '昵称至少:min 位字符',
            'nickname.max' => '昵称最多:max 位字符',
            'gender.enum' => '性别参数不正确',
            'birthday.date' => '生日格式不正确',
        ];
    }
}
