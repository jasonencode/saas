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
            'file.file' => '上传的必须是文件',
            'file.image' => '上传的必须是图片',
            'file.mimes' => '图片格式不支持，仅支持 jpg,jpeg,png,gif,bmp,webp',
            'file.max' => '图片大小不可超过 :max KB',
        ];
    }
}
