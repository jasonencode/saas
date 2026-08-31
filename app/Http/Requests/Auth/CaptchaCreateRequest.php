<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\BaseFormRequest;

class CaptchaCreateRequest extends BaseFormRequest
{
    /**
     * 支持的验证码类型（对应 captcha 配置中的样式组）
     *
     * @var array<int, string>
     */
    public const array TYPES = ['default', 'math', 'number', 'flat', 'mini', 'inverse'];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'type' => [
                'nullable',
                'string',
                'in:'.implode(',', self::TYPES),
            ],
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
            'type.in' => '验证码类型无效',
        ];
    }

    /**
     * 获取验证码类型，未指定时使用默认类型
     *
     * @return string 验证码类型
     */
    public function captchaType(): string
    {
        return $this->validated('type') ?? 'default';
    }
}
