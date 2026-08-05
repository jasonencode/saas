<?php

namespace App\Http\Requests;

use App\Enums\Content\PlatformType;
use Illuminate\Validation\Rule;

class VersionRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'platform' => [
                'required',
                Rule::enum(PlatformType::class),
            ],
            'application_id' => 'required',
            'version' => 'required',
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
            'platform.required' => '平台类型必须填写',
            'platform.enum' => '平台类型不正确',
            'application_id.required' => '应用包名必须填写',
            'version.required' => '版本号必须填写',
        ];
    }
}
