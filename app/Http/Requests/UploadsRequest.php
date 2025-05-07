<?php

namespace App\Http\Requests;

class UploadsRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'files' => 'required|array',
            'files.*' => [
                'file',
                'image:jpg,jpeg,png,gif',
//                'max:'.config('storage.FRONT_MAX_FILE_SIZE'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => '图片必须选择',
            'files.array' => '图片字段格式不正确',
            'files.*.file' => '图片格式不正确',
            'files.*.image' => '图片格式不允许',
            'files.*.max' => '图片大小不可超过 :max KB',
        ];
    }
}
