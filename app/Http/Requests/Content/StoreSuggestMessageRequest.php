<?php

namespace App\Http\Requests\Content;

use App\Http\Requests\BaseFormRequest;

class StoreSuggestMessageRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'content' => 'required|string|min:1|max:500',
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
            'content.required' => '请填写消息内容',
            'content.min' => '消息内容至少:min个字',
            'content.max' => '消息内容不能超过:max个字',
        ];
    }
}
