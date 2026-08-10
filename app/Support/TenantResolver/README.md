# TenantResolver

多租户解析器，从 HTTP 请求头 `X-Tenant-Id` 中提取租户标识，验证状态和有效期，缓存结果供请求生命周期使用。

## 架构

```
Request Header (X-Tenant-Id)
    ↓
TenantResolver::resolve()
    ↓ Cache::remember(1h)
    ↓ Context 绑定（单请求单次解析）
    ↓
Tenant Model
```

## 核心类

| 类 | 职责 |
|----|------|
| `TenantResolver` | 静态解析器，读取请求头 → 查询租户 → 缓存 → 校验状态/过期 |

## 使用方式

### 全局宏

`AppServiceProvider` 注册了 Request 宏：

```php
$tenant = request()->tenant();
```

### 直接调用

```php
use App\Support\TenantResolver\TenantResolver;

$tenant = TenantResolver::current();
```

### 中间件中使用

```php
$tenant = TenantResolver::current();

if (!$tenant || $tenant->isExpired()) {
    abort(403, '租户已过期');
}
```

## 解析流程

1. 读取 `X-Tenant-Id` 请求头，无则返回 `null`
2. 通过 `Cache::remember()` 缓存 1 小时（key: `tenant:{id}`）
3. 使用 `Context` 绑定确保单请求只解析一次
4. 校验：不存在返回 400，已禁用返回 403，已过期返回 403

## 依赖

- `App\Models\System\Tenant` — 租户模型
