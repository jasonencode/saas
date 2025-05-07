<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'old_pass' => 'required|min:6|current_password',
            'new_pass' => [
                'required',
                Password::min(6)->max(20),
            ],
            're_pass' => [
                'required',
                'same:new_pass',
            ],
        ];
    }
}
