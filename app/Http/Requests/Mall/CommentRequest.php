<?php

namespace App\Http\Requests\Mall;

use App\Http\Requests\BaseFormRequest;

class CommentRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'star' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],
            'content' => [
                'required',
                'string',
                'min:5',
                'max:500',
            ],
            'pictures' => [
                'nullable',
                'array',
                'max:9',
            ],
            'pictures.*' => [
                'string',
                'max:500',
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
            'star.required' => '请给出评分',
            'star.integer' => '评分必须是整数',
            'star.min' => '评分最少为1星',
            'star.max' => '评分最多为5星',
            'content.required' => '请填写评价内容',
            'content.min' => '评价内容至少5个字符',
            'content.max' => '评价内容最多500个字符',
            'pictures.max' => '最多上传9张图片',
        ];
    }
}
