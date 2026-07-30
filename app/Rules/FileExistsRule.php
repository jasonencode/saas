<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Storage;

class FileExistsRule implements ValidationRule
{
    public function __construct(protected ?string $message = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || trim($value) === '') {
            $fail($this->message ?? '文件路径无效');

            return;
        }

        if (!Storage::exists($value)) {
            $fail($this->message ?? '文件不存在，请检查');
        }
    }
}
