# 频率限制配置说明

## 📋 概述

本项目使用 Laravel 的 Rate Limiter 来防止恶意请求和滥用。所有频率限制配置都集中在 `config/custom.php` 的 `rate_limits` 子配置中管理。

## 🔧 配置位置

配置文件位于：`config/custom.php`

```php
'rate_limits' => [
    'api' => env('RATE_LIMIT_API', 60),
    'upload' => env('RATE_LIMIT_UPLOAD', 10),
    'login' => env('RATE_LIMIT_LOGIN', 5),
    'sms' => env('RATE_LIMIT_SMS', 2),
    'register' => env('RATE_LIMIT_REGISTER', 3),
    'password_reset' => env('RATE_LIMIT_PASSWORD_RESET', 3),
    'default' => env('RATE_LIMIT_DEFAULT', 30),
],
```

## ✅ 已注册的限流器

所有 7 个限流器已在 `AppServiceProvider` 中完成注册：

| Limiter 名称 | 配置项 | 默认值 | 限制对象 | 状态 |
|-------------|--------|--------|---------|------|
| `api` | `custom.rate_limits.api` | 60 | 用户 ID 或 IP | ✅ 已注册 |
| `uploads` | `custom.rate_limits.upload` | 10 | 用户 ID 或 IP | ✅ 已注册 |
| `login` | `custom.rate_limits.login` | 5 | IP | ✅ 已注册 |
| `sms` | `custom.rate_limits.sms` | 2 | IP | ✅ 已注册 |
| `register` | `custom.rate_limits.register` | 3 | IP | ✅ 已注册 |
| `password-reset` | `custom.rate_limits.password_reset` | 3 | IP | ✅ 已注册 |
| `default` | `custom.rate_limits.default` | 30 | 用户 ID 或 IP | ✅ 已注册 |

### 📍 注册位置

文件：[`app/Providers/AppServiceProvider.php`](../app/Providers/AppServiceProvider.php#L38-L81)

## ⚠️ 重要说明

**必须注册 Rate Limiter！**

仅仅在配置文件中定义是不够的，必须在 Service Provider 中注册才能使用。

### ✅ 正确的实现方式

在 `app/Providers/AppServiceProvider.php` 的 `boot()` 方法中注册：

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    // 注册 API 限流器
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(config('custom.rate_limits.api'))
            ->by(optional($request->user())->id ?: $request->ip());
    });
    
    // 注册其他限流器...
}
```

### ❌ 错误的理解

```php
// ❌ 错误：仅仅有配置是不能工作的
config(['rate_limits' => [...]]); // 这不会自动注册 limiter

// ❌ 错误：limiter 不会自动创建
// 即使配置了 config('custom.rate_limits.sms')，也必须手动调用 RateLimiter::for('sms')
```

### 1. API 接口访问频率 (`api`)

- **配置项**: `RATE_LIMIT_API`
- **默认值**: `60` 次/分钟
- **说明**: 普通 API 接口的访问频率限制
- **建议**:
  - 生产环境：60-120 次/分钟
  - 开发环境：可适当提高到 120-200 次/分钟
  - 内部测试环境：可设置为 0（不限制）

### 2. 文件上传频率 (`upload`)

- **配置项**: `RATE_LIMIT_UPLOAD`
- **默认值**: `10` 次/分钟
- **说明**: 防止恶意上传攻击
- **建议**: 5-10 次/分钟
- **场景**: 图片上传、文件上传等接口

### 3. 登录尝试频率 (`login`)

- **配置项**: `RATE_LIMIT_LOGIN`
- **默认值**: `5` 次/分钟
- **说明**: 防止暴力破解密码
- **建议**: 5-10 次/分钟（安全优先）
- **场景**: 用户登录、管理员登录

### 4. 短信发送频率 (`sms`)

- **配置项**: `RATE_LIMIT_SMS`
- **默认值**: `2` 次/分钟
- **说明**: 防止短信轰炸和恶意消耗配额
- **建议**: 2-5 次/分钟（严格控制）
- **场景**: 验证码发送、通知短信

### 5. 注册频率 (`register`)

- **配置项**: `RATE_LIMIT_REGISTER`
- **默认值**: `3` 次/分钟
- **说明**: 防止批量注册垃圾账号
- **建议**: 3-5 次/分钟
- **场景**: 用户注册接口

### 6. 密码重置频率 (`password_reset`)

- **配置项**: `RATE_LIMIT_PASSWORD_RESET`
- **默认值**: `3` 次/分钟
- **说明**: 防止恶意重置密码骚扰用户
- **建议**: 3 次/分钟
- **场景**: 忘记密码、重置密码

### 7. 通用后备频率 (`default`)

- **配置项**: `RATE_LIMIT_DEFAULT`
- **默认值**: `30` 次/分钟
- **说明**: 当其他 limiter 未定义时的默认值
- **建议**: 30-60 次/分钟

## 📝 环境变量配置

在 `.env` 文件中配置：

```bash
# 频率限制配置
RATE_LIMIT_API=60
RATE_LIMIT_UPLOAD=10
RATE_LIMIT_LOGIN=5
RATE_LIMIT_SMS=2
RATE_LIMIT_REGISTER=3
RATE_LIMIT_PASSWORD_RESET=3
RATE_LIMIT_DEFAULT=30
```

## 💡 使用示例

### 在控制器中使用

```php
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

// 自定义 limiter
RateLimiter::for('custom', function (Request $request) {
    return Limit::perMinute(config('custom.rate_limits.api'))
        ->by($request->user()->id);
});
```

### 在路由中间件中使用

```php
use Illuminate\Cache\Middleware\ThrottleRequests;

Route::middleware(['throttle:api'])
    ->group(function () {
        Route::get('/users', [UserController::class, 'index']);
    });

// 或指定特定的 limiter
Route::middleware(['throttle:login'])
    ->post('/login', [AuthController::class, 'login']);
```

### 手动检查限流

```php
if (RateLimiter::tooManyAttempts('api', $request->ip())) {
    $seconds = RateLimiter::availableIn('api', $request->ip());
    return response()->json([
        'message' => '请求过于频繁，请' . $seconds . '秒后再试',
    ], 429);
}

RateLimiter::hit('api', $request->ip());
```

## 🎯 最佳实践

### 1. 根据业务场景调整

```php
// VIP 用户更高的限制
RateLimiter::for('api-vip', function (Request $request) {
    return $request->user()->isVip()
        ? Limit::perMinute(200)->by($request->user()->id)
        : Limit::perMinute(config('custom.rate_limits.api'))->by($request->user()->id);
});
```

### 2. 动态调整限制

```php
// 根据时间段调整
$limit = now()->hour >= 9 && now()->hour <= 18 
    ? config('custom.rate_limits.api') * 2  // 工作时间加倍
    : config('custom.rate_limits.api');

return Limit::perMinute($limit)->by($request->ip());
```

### 3. 组合使用多个限制

```php
// 同时限制 IP 和用户
RateLimiter::for('strict', function (Request $request) {
    return [
        Limit::perMinute(60)->by($request->ip()),
        Limit::perMinute(100)->by(optional($request->user())->id),
    ];
});
```

## ⚠️ 注意事项

1. **不要设置过低**: 避免影响正常用户使用
2. **不要设置过高**: 起不到防护作用
3. **监控告警**: 建议配合监控系统，当触发限流时发送告警
4. **白名单**: 为内部 IP 或测试账号设置白名单
5. **灵活调整**: 根据实际业务数据和攻击情况动态调整

## 📊 推荐配置表

| 环境 | API | Upload | Login | SMS | Register |
|------|-----|--------|-------|-----|----------|
| 生产环境 | 60 | 10 | 5 | 2 | 3 |
| 开发环境 | 120 | 20 | 10 | 5 | 5 |
| 测试环境 | 0 | 0 | 0 | 0 | 0 |

## 🔍 监控与调试

### 查看当前限流状态

```php
$key = 'api:' . $request->ip();
$attempts = Cache::get($key, 0);
$maxAttempts = config('custom.rate_limits.api');

echo "当前尝试次数：{$attempts}, 最大限制：{$maxAttempts}";
```

### 清除限流计数

```php
// 清除特定用户的限流
Cache::forget('api:' . $request->user()->id);

// 清除所有限流（谨慎使用）
Cache::flush();
```

---

**最后更新**: 2026-03-27  
**维护者**: SaaS.Foundation Team
