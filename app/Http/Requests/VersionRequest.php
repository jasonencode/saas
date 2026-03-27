<?php

namespace App\Http\Requests;

use App\Enums\PlatformType;
use Illuminate\Validation\Rule;

class VersionRequest extends BaseFormRequest
{
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
