<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseFormRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    abstract public function rules(): array;
}
