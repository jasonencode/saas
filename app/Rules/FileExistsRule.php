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
     * - 本地/公有云 URL：https://example.com/storage/0/2026/08/25/xxx.jpg → 0/2026/08/25/xxx.jpg
     * - S3/OSS path-style URL：https://s3.example.com/bucket/0/2026/08/25/xxx.jpg → 0/2026/08/25/xxx.jpg
     * - 相对路径：/storage/0/2026/08/25/xxx.jpg → 0/2026/08/25/xxx.jpg
     * - 存储路径：0/2026/08/25/xxx.jpg → 0/2026/08/25/xxx.jpg
     */
    protected function extractStoragePath(string $value): string
    {
        // 已经是相对路径，直接返回
        if (!str_starts_with($value, 'http')) {
            return $value;
        }

        $path = parse_url($value, PHP_URL_PATH);

        if ($path === false || $path === null) {
            return $value;
        }

        $path = ltrim($path, '/');

        // 本地公有云磁盘：URL 带 /storage/ 前缀
        if (preg_match('#^storage/(.+)$#', $path, $matches)) {
            return $matches[1];
        }

        // S3/OSS path-style URL：路径以 bucket 名开头（如 /bucket/key）
        $diskName = config('filesystems.default');
        $bucket = config("filesystems.disks.{$diskName}.bucket");

        if ($bucket && str_starts_with($path, $bucket.'/')) {
            return substr($path, strlen($bucket) + 1);
        }

        return $path;
    }
}
