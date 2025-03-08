<?php

namespace App\Http\Requests;

class UploadRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'file' => [
                'bail',
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,gif,bmp,webp',
//                'max:'.config('storage.FRONT_MAX_FILE_SIZE'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => '图片必须上传',
            'file.file' => '图片格式不正确',
            'file.image' => '非法图片格式',
            'file.mimes' => '图片格式不允许',
            'file.max' => '图片大小不可超过 :max KB',
        ];
    }
}