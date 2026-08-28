# 频率限制配置说明

## 📋 概述

系统使用 Laravel 内置的 `RateLimiter` 实现多维度频率限制，防止滥用和攻击。限流器在 `AppServiceProvider` 中集中定义，配置项位于 `config/custom.php` 的 `rate_limits` 数组。

## 🏗️ 架构设计

### 限流器定义

```php
// app/Providers/AppServiceProvider.php
protected function bootRateLimiters(): void
{
    // 全局 API 频率限制
    RateLimiter::for('api', static function (Request $request) {
        return Limit::perMinute(config('custom.rate_limits.api'))
            ->by(optional($request->user())->id ?: $request->ip());
    });

    // 文件上传频率限制（基于 IP）
    RateLimiter::for('uploads', static function (Request $request) {
        return Limit::perMinute(config('custom.rate_limits.uploads'))
            ->by($request->ip());
    });

    // 登录尝试频率限制
    RateLimiter::for('login', static function (Request $request) {
        return Limit::perMinute(config('custom.rate_limits.login'))
            ->by($request->ip());
    });

    // 短信发送频率限制
    RateLimiter::for('sms', static function (Request $request) {
        return Limit::perMinute(config('custom.rate_limits.sms'))
            ->by($request->ip());
    });

    // 用户注册频率限制
    RateLimiter::for('register', static function (Request $request) {
        return Limit::perMinute(config('custom.rate_limits.register'))
            ->by($request->ip());
    });

    // 密码重置频率限制
    RateLimiter::for('password-reset', static function (Request $request) {
        return Limit::perMinute(config('custom.rate_limits.password_reset'))
            ->by($request->ip());
    });

    // 默认频率限制（后备）
    RateLimiter::for('default', static function (Request $request) {
        return Limit::perMinute(config('custom.rate_limits.default'))
            ->by(optional($request->user())->id ?: $request->ip());
    });
}
```

### 配置项

```php
// config/custom.php
'reate_limits' => [
    'api' => env('RATE_LIMIT_API', 60),
    'uploads' => env('RATE_LIMIT_UPLOADS', 10),
    'login' => env('RATE_LIMIT_LOGIN', 5),
    'sms' => env('RATE_LIMIT_SMS', 1),
    'register' => env('RATE_LIMIT_REGISTER', 1),
    'password_reset' => env('RATE_LIMIT_PASSWORD_RESET', 3),
    'default' => env('RATE_LIMIT_DEFAULT', 60),
],
```

| 配置项 | 默认值 | 说明 |
|--------|--------|------|
| `api` | 60 | 全局 API 限制（次/分钟），按用户或 IP |
| `uploads` | 10 | 文件上传限制（次/分钟），按 IP |
| `login` | 5 | 登录尝试限制（次/分钟），按 IP |
| `sms` | 1 | 短信发送限制（次/分钟），按 IP |
| `register` | 1 | 用户注册限制（次/分钟），按 IP |
| `password_reset` | 3 | 密码重置限制（次/分钟），按 IP |
| `default` | 60 | 后备默认限制（次/分钟），按用户或 IP |

> 所有值均可通过对应的 `RATE_LIMIT_*` 环境变量覆盖。

### 维度区分

| 限流器 | 维度 | 说明 |
|--------|------|------|
| api / default | `user->id ?? IP` | 已登录按用户 ID，未登录按 IP |
| uploads / login / sms / register / password-reset | IP | 一律按 IP 计算，防止同一账号多设备绕过 |

---

## 🚀 使用方法

### 1. 在路由中应用限流

```php
// routes/apis/mall.php
Route::middleware('throttle:api')
    ->group(function () {
        // ...
    });

// 应用特定限流器
Route::middleware('throttle:login')
    ->post('/auth/password', [LoginController::class, 'password']);

Route::middleware('throttle:sms')
    ->post('/auth/sms', [LoginController::class, 'sms']);
```

`routes/apis/` 下各模块统一挂载 `throttle:api`；`/auth` 模块对登录、短信接口分别使用 `login` / `sms` 限流器。

### 2. 在控制器中应用限流

```php
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

public function sms(Request $request)
{
    // 自定义限流逻辑（按手机号）
    $key = 'sms:'.$request->input('mobile');

    if (RateLimiter::tooManyAttempts($key, 1)) {
        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'mobile' => "短信发送过于频繁，请在 {$seconds} 秒后重试",
        ]);
    }

    RateLimiter::hit($key, 60);
}
```

---

## 📊 响应头

超限或正常请求都会返回以下响应头：

| 响应头 | 说明 |
|--------|------|
| `X-RateLimit-Limit` | 允许的最大请求数 |
| `X-RateLimit-Remaining` | 剩余请求数 |
| `X-RateLimit-Reset` | 限流重置的 UNIX 时间戳 |

超限后返回 **429 Too Many Requests**，`Retry-After` 响应头指示重试时间（秒）。

```
HTTP/1.1 429 Too Many Requests
Retry-After: 60
X-RateLimit-Limit: 5
X-RateLimit-Remaining: 0
```

---

## 💡 最佳实践

1. **敏感接口单独限流** - 登录、短信、注册等敏感操作使用独立且更严格的限流器
2. **多维度组合** - 可结合用户 ID + IP + 设备指纹等维度
3. **动态调整** - 通过环境变量按环境（开发 / 生产）调整阈值
4. **友好提示** - 429 响应中返回清晰的错误信息和重试时间
5. **监控告警** - 记录高频访问日志，发现异常流量时及时告警
