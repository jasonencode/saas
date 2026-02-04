<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use App\Enums\PlatformType;

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
            'platform.required' => '平台必填',
            'application_id.required' => '应用包名必填',
            'version.required' => '版本号必填',
        ];
    }
}

