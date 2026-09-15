# 频率限制配置说明

## 📋 概述

系统使用 Laravel 内置的 `RateLimiter` 实现多维度频率限制，防止滥用和攻击。限流器在 `AppServiceProvider` 中集中定义，配置项位于 `config/custom.php` 的 `rate_limits` 数组。

## 🏗️ 架构设计

### 限流器定义

```php
// app/Providers/AppServiceProvider.php
protected function bootRateLimiter(): void
{
    // 全局 API 频率限制
    RateLimiter::for('api', static function (Request $request) {
        return Limit::perMinute(config('custom.rate_limits.api'))
            ->by(optional($request->user())->id ?: $request->ip());
    });

    // 文件上传频率限制
    RateLimiter::for('uploads', static function (Request $request) {
        return Limit::perMinute(config('custom.rate_limits.upload'))
            ->by(optional($request->user())->id ?: $request->ip());
    });

    // 登录尝试频率限制：IP+账号 与 单 IP 双层
    RateLimiter::for('login', static function (Request $request) {
        $ip = $request->ip();
        $limits = [Limit::perMinute(config('custom.rate_limits.login_ip'))->by('ip:'.$ip)];

        if ($account = $request->input('username')) {
            $limits[] = Limit::perMinute(config('custom.rate_limits.login'))->by('account:'.$ip.'|'.$account);
        }

        return $limits;
    });

    // 短信发送频率限制：IP+手机号 与 单 IP 双层
    RateLimiter::for('sms', static function (Request $request) {
        $ip = $request->ip();
        $limits = [Limit::perMinute(config('custom.rate_limits.sms_ip'))->by('ip:'.$ip)];

        if ($mobile = $request->input('mobile')) {
            $limits[] = Limit::perMinute(config('custom.rate_limits.sms'))->by('mobile:'.$ip.'|'.$mobile);
        }

        return $limits;
    });

    // 用户注册频率限制
    RateLimiter::for('register', static function (Request $request) {
        return Limit::perMinute(config('custom.rate_limits.register'))
            ->by($request->ip());
    });

    // 租户令牌频率限制（机器对机器，按 app_key 区分调用方）
    RateLimiter::for('tenant', static function (Request $request) {
        return Limit::perMinute(config('custom.rate_limits.tenant'))
            ->by($request->input('app_key') ?: $request->ip());
    });
}
```

### 配置项

```php
// config/custom.php
'rate_limits' => [
    'api' => env('RATE_LIMIT_API', 60),              // 每用户(已登录)或每 IP
    'upload' => env('RATE_LIMIT_UPLOAD', 10),        // 每用户(已登录)或每 IP
    'login' => env('RATE_LIMIT_LOGIN', 5),           // 每 IP+账号
    'login_ip' => env('RATE_LIMIT_LOGIN_IP', 20),    // 每 IP(登录接口总上限)
    'sms' => env('RATE_LIMIT_SMS', 2),               // 每 IP+手机号
    'sms_ip' => env('RATE_LIMIT_SMS_IP', 20),        // 每 IP(短信接口总上限)
    'register' => env('RATE_LIMIT_REGISTER', 3),     // 每 IP
    'tenant' => env('RATE_LIMIT_TENANT', 60),        // 每 app_key
],
```

| 配置项 | 默认值 | 说明 |
|--------|--------|------|
| `api` | 60 | 全局 API 限制（次/分钟），按用户或 IP |
| `upload` | 10 | 文件上传限制（次/分钟），按用户或 IP |
| `login` | 5 | 登录尝试限制（次/分钟），按 IP+账号 |
| `login_ip` | 20 | 登录接口单 IP 总上限（次/分钟） |
| `sms` | 2 | 短信发送限制（次/分钟），按 IP+手机号 |
| `sms_ip` | 20 | 短信接口单 IP 总上限（次/分钟） |
| `register` | 3 | 用户注册限制（次/分钟），按 IP |
| `tenant` | 60 | 租户令牌限制（次/分钟），按 app_key |

> 所有值均可通过对应的 `RATE_LIMIT_*` 环境变量覆盖。

### 维度区分

| 限流器 | 维度 | 说明 |
|--------|------|------|
| api | `user->id ?? IP` | 已登录按用户 ID，未登录按 IP |
| uploads | `user->id ?? IP` | 同上 |
| login | `IP+账号` + `IP` | 双层。账号维度带 IP 前缀，避免攻击者用错误密码锁死他人账号；单 IP 上限兜底，防止同一出口更换账号刷接口 |
| sms | `IP+手机号` + `IP` | 双层。手机号维度防止对单一号码轰炸，单 IP 上限防止批量换号 |
| register | `IP` | 注册量本身就是按来源控制，用户名唯一性由 `users.username` 约束兜底 |
| tenant | `app_key` | 机器对机器接口，按调用方区分，阈值宽松 |

> 返回 `Limit[]` 时，任一维度超限即返回 429；`X-RateLimit-*` 响应头反映数组中的**最后一个**维度。

> 各限流器按键前缀（`ip:` / `account:` / `mobile:`）互相隔离。若两层使用相同键，`ThrottleRequests` 会对同一计数器 `hit()` 两次，实际额度会腰斩。

---

## 🚀 使用方法

### 1. 全局 API 限流

`throttle:api` 挂载在 `bootstrap/app.php` 的 api 中间件组上，`routes/apis/` 下所有模块自动生效，无需在各自路由文件里重复声明：

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->api([
        'throttle:api',
    ]);
})
```

### 2. 敏感接口单独限流

在路由上追加对应的限流器即可（与 `throttle:api` 叠加，两者分别计数）：

```php
// routes/apis/auth.php
$router->post('sms', [SmsController::class, 'send'])
    ->middleware('throttle:sms');

$router->post('password', [LoginController::class, 'password'])
    ->middleware('throttle:login');

$router->post('tenant', [LoginController::class, 'tenant'])
    ->middleware('throttle:tenant');
```

当前接入情况：

| 限流器 | 路由 |
|--------|------|
| `login` | `POST /api/auth/password`、`POST /api/auth/mini/phone` |
| `sms` | `POST /api/auth/sms` |
| `register` | `POST /api/auth/register` |
| `tenant` | `POST /api/auth/tenant` |
| `uploads` | `POST /api/system/upload/image`、`POST /api/system/upload/images` |
| `api` | 全部 api 路由（`bootstrap/app.php` 中间件组） |

> `GET /api/auth/captcha` 未单独限流，仅受 `throttle:api` 约束。

### 3. 在控制器中应用限流

路由级限流已覆盖登录 / 短信 / 注册 / 上传等维度。仅当需要**额外**维度（如按天配额、按设备指纹）时，才在控制器里手动调用：

```php
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

// 例：单手机号每日短信总量上限（路由级的 sms 限流器只管每分钟）
$key = 'sms:daily:'.$request->input('mobile');

if (RateLimiter::tooManyAttempts($key, 10)) {
    throw ValidationException::withMessages([
        'mobile' => '今日短信发送次数已达上限',
    ]);
}

RateLimiter::hit($key, 86400);
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
