<?php

namespace App\Http\Requests\Content;

use App\Http\Requests\BaseFormRequest;

class StoreCommentRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'content' => 'required_without:pictures|string|max:2000',
            'star' => 'nullable|integer|min:1|max:5',
            'pictures' => 'nullable|array',
            'pictures.*' => 'string|max:500',
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
            'content.required_without' => '评论内容或图片至少填写一项',
            'content.string' => '评论内容格式不正确',
            'content.max' => '评论内容最多:max位字符',
            'star.integer' => '评分格式不正确',
            'star.min' => '评分最小为:min',
            'star.max' => '评分最大为:max',
            'pictures.array' => '图片格式不正确',
            'pictures.*.string' => '图片地址格式不正确',
            'pictures.*.max' => '图片地址最多:max位字符',
        ];
    }
}
