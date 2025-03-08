<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Rules\FileExistsRule;
use Illuminate\Validation\Rule;

class UpdateUserInfoRequest extends BaseFormRequest
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
}