# 多租户架构

## 概述

Saas.Foundation 内置多租户支持，租户模型为 `App\Models\System\Tenant`。项目提供三个运行视角：

| 视角 | 入口 | 说明 |
|------|------|------|
| 平台面板 | `/backend`（`BACKEND_DOMAIN`） | 平台方管理租户、全局配置、各业务集群 |
| 租户面板 | `/tenant`（`TENANT_DOMAIN`） | 租户方管理自己店铺的业务数据，按域名识别租户 |
| API | `API_DOMAIN` + `X-Tenant-Id` 请求头 | 小程序 / 第三方客户端调用，按请求头解析租户 |

租户实现 `HasName`、`HasAvatar`、`HasCurrentTenantLabel` 接口，可直接用于 Filament 多租户。

## 面板侧租户识别

租户面板使用 Filament 内置的多租户能力：

```php
// app/Providers/TenantPanelProvider.php
->tenant(Tenant::class, 'slug')          // 按 slug 匹配域名
->domain(config('custom.domains.tenant_domain'))
->tenantMiddleware([
    EnsureTenantNotExpired::class,        // 租户过期时重定向到过期页
])
```

- 租户域名 = `{tenant.slug}.{TENANT_DOMAIN}`，Filament 的 `IdentifyTenant` 中间件按 slug 自动识别
- `EnsureTenantNotExpired` 在识别之后执行：`expired_at` 早于当前时间时，除过期提示页外全部重定向

## 数据隔离

### BelongsToTenant trait

业务模型通过 `App\Models\Traits\BelongsToTenant` 关联租户：

```php
<?php

namespace App\Models\Traits;

use App\Models\System\Tenant;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * 关联租户
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * 租户作用域
     */
    #[Scope]
    protected function ofTenant(Builder $query, Tenant|int|null $tenant = null): void
    {
        if (is_int($tenant)) {
            $query->where('tenant_id', $tenant);
        } elseif ($tenant instanceof Tenant) {
            $query->whereBelongsTo($tenant);
        }
    }
}
```

使用方式：

```php
// 按当前租户查询
Product::ofTenant($tenant)->get();
Product::ofTenant(1)->get();
```

迁移中统一用 `Blueprint::macro('tenant')` 快捷方法写入租户字段：

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->tenant();   // 等价于 foreignId('tenant_id')->constrained()->cascadeOnDelete()
    // ...
});
```

## API 侧租户解析

API 请求不带域名，租户由 `X-Tenant-Id` 请求头解析，实现见 `App\Support\TenantResolver\TenantResolver`：

```php
$tenant = TenantResolver::current();   // 解析当前请求的租户
```

解析规则：

1. 读取请求头 `X-Tenant-Id`（无头时返回 `null`）
2. 命中 Redis 缓存（`tenant_data:v3:{id}`，1 小时）则直接使用缓存数组构造模型
3. 校验租户存在、未禁用、未过期，否则抛出 400 / 403
4. 解析结果存入 `Illuminate\Support\Facades\Context`，同一请求内复用

相关中间件：

- `EnsureStoreIsOpened`（`store.opened`）：校验当前租户的商城已开通（`StoreConfigure.enabled`），未开通统一返回 403
- `EnsureTenantNotExpired`：租户过期拦截（面板侧）

租户调用 API 获取 Token 的签名认证方式见 [租户 API 签名认证](tenant-auth)。

## 租户与用户的关系

- 租户与用户多对多：`user_tenant` 关联表（`UserTenant` pivot 模型）
- `User::tenants()`：用户所属租户列表
- `Tenant::users()`：租户内用户列表
- `Tenant::administrators()` / `roles()`：租户管理员与角色

租户创建时自动初始化权限（`Tenant::boot()` 中调用 `TenantService::initializePermissions()`）。

## 租户状态

| 状态 | 说明 |
|------|------|
| 启用 / 禁用 | `status` 字段（`HasEasyStatus` trait），禁用后 API 签名认证直接拒绝 |
| 过期 | `expired_at`，过期后 API 与租户面板均拦截 |
