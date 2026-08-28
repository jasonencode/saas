<?php

namespace App\Filament\Pages\Concerns;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;

/**
 * 默认隐藏验证码，登录失败后才显示，登录成功后恢复隐藏。
 *
 * 标记存放于 session，刷新页面不会丢失；键名按页面类区分，
 * 保证运营端与租户端互不干扰。
 *
 * 配合 CaptchaInput 使用时无需改动其 required 规则：Filament 对
 * 隐藏且 dehydratedWhenHidden 为 false 的字段会跳过校验。
 *
 * 注意：session 方案可被不带 Cookie 的脚本绕过，暴力破解防护仍
 * 依赖父类 Login::authenticate() 中按 IP 的 rateLimit(5)。
 */
trait RequiresCaptchaAfterFailure
{
    /**
     * 登录成功后清除验证码标记
     *
     * 父类返回 null 表示触发限流或需要多因素挑战，均不算成功；
     * 失败路径抛异常走不到这里。session()->regenerate() 会保留
     * 原有数据，因此必须显式 forget。
     */
    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();

        if ($response !== null) {
            session()->forget($this->getCaptchaSessionKey());
        }

        return $response;
    }

    /**
     * 当前是否需要显示验证码
     */
    protected function isCaptchaRequired(): bool
    {
        return session()->has($this->getCaptchaSessionKey());
    }

    /**
     * 标记后续登录需要验证码
     */
    protected function markCaptchaRequired(): void
    {
        session()->put($this->getCaptchaSessionKey(), true);
    }

    /**
     * 标记在 session 中的键名，按页面类区分所属面板
     */
    protected function getCaptchaSessionKey(): string
    {
        return 'login.captcha_required.'.static::class;
    }
}
