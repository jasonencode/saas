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
            'files.required' => '必须选择要上传的图片',
            'files.array' => '图片字段格式不正确',
            'files.*.file' => '上传的必须是文件',
            'files.*.image' => '上传的必须是图片',
            'files.*.max' => '图片大小不可超过 :max KB',
        ];
    }
}
