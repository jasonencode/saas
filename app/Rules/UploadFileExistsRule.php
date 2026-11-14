<?php

namespace App\Rules;

use App\Enums\Foundation\FileVisibility;
use App\Models\Foundation\UploadFile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * 校验上传文件 UUID 是否存在
 *
 * 仅匹配当前登录用户的上传记录，避免引用他人上传的文件；
 * 传入可见性时同时校验文件所在磁盘，防止把公开文件当作私有文件提交。
 *
 * 用法示例：
 * ```
 * 'id_card_front' => ['required', 'string', new UploadFileExistsRule(FileVisibility::Private)],
 * ```
 */
class UploadFileExistsRule implements ValidationRule
{
    /**
     * 构造规则
     *
     * @param  string|null  $message  自定义错误信息
     * @param  FileVisibility|null  $visibility  文件可见性，传入时校验文件所在磁盘；null 不限制
     */
    public function __construct(
        protected ?string $message = null,
        protected ?FileVisibility $visibility = null,
    ) {}

    /**
     * 校验上传文件 UUID
     *
     * @param  string  $attribute  验证字段名
     * @param  mixed  $value  上传文件 UUID
     * @param  Closure  $fail  失败回调
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !Str::isUuid($value)) {
            $fail($this->message ?? '文件标识无效');

            return;
        }

        $query = UploadFile::whereKey($value);

        if ($user = Auth::user()) {
            $query->where('user_id', $user->getKey());
        }

        if ($this->visibility) {
            $query->where('disk', $this->visibility->disk());
        }

        if (!$query->exists()) {
            $fail($this->message ?? '文件不存在，请重新上传');
        }
    }
}
