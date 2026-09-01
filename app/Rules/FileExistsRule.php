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
     * @param  mixed  $value  文件路径或URL
     * @param  Closure  $fail  失败回调
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || trim($value) === '') {
            $fail($this->message ?? '文件路径无效');

            return;
        }

        $path = $this->extractStoragePath($value);

        if (!Storage::exists($path)) {
            $fail($this->message ?? '文件不存在，请检查');
        }
    }

    /**
     * 从URL或路径中提取存储路径
     *
     * 支持格式：
     * - 完整URL：https://example.com/storage/0/2026/08/25/xxx.jpg → 0/2026/08/25/xxx.jpg
     * - 相对路径：/storage/0/2026/08/25/xxx.jpg → 0/2026/08/25/xxx.jpg
     * - 存储路径：0/2026/08/25/xxx.jpg → 0/2026/08/25/xxx.jpg
     */
    protected function extractStoragePath(string $value): string
    {
        if (preg_match('#/storage/(.+)$#', $value, $matches)) {
            return $matches[1];
        }

        return $value;
    }
}
