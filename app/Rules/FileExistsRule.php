<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Storage;

/**
 * 检查文件是否存在于默认磁盘
 *
 * 用法示例：
 * ```
 * 'file' => [new FileExistsRule],
 * ```
 */
class FileExistsRule implements ValidationRule
{
    /**
     * 创建文件存在验证规则
     *
     * @param  string|null  $message  自定义错误消息
     */
    public function __construct(protected ?string $message = null) {}

    /**
     * 验证文件是否存在
     *
     * @param  string  $attribute  验证字段名
     * @param  mixed  $value  文件路径
     * @param  Closure  $fail  失败回调
     */
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
