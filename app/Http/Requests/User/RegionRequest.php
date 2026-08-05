<?php

namespace App\Http\Requests;

class RegionRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'parent_id' => 'sometimes|integer|min:0',
            'layer' => 'sometimes|integer|in:1,2',
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
            'parent_id.integer' => '父级ID格式不正确',
            'parent_id.min' => '父级ID最小为:min',
            'layer.integer' => '层级格式不正确',
            'layer.in' => '层级只能是1或2',
        ];
    }
}
