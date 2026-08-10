# PolicyPermission

权限树构建器，通过反射扫描所有注册的 Gate 策略，提取 `PolicyName` 属性注解，构建层级化权限树用于 Filament 角色管理。

## 工作流程

```
Gate::policies()
    ↓ 反射实例化
Policy (检查平台权限)
    ↓ 扫描公共方法
PolicyName Attribute (提取权限名称)
    ↓ 按平台过滤
权限树 Collection
```

## 核心类

| 类 | 职责 |
|----|------|
| `PolicyPermission` | 静态工具：`tree()` 扫描策略，返回按分组组织的权限树 |

## 使用方式

```php
use App\Support\PolicyPermission\PolicyPermission;
use App\Enums\System\PolicyPlatform;

// Backend 面板权限树
$permissions = PolicyPermission::tree(PolicyPlatform::Backend);

// Tenant 面板权限树
$permissions = PolicyPermission::tree(PolicyPlatform::Tenant);
```

## 返回结构

```php
[
    [
        'method' => 'App\Policies\UserPolicy',
        'name' => '用户',
        'group' => '用户管理',
        'platform' => PolicyPlatform::Both,
        'children' => [
            [
                'method' => 'App\Policies\UserPolicy@update',
                'name' => '编辑用户',
                'platform' => PolicyPlatform::Both,
                'description' => '允许编辑用户信息',
                'type' => 'permission',
            ],
        ],
    ],
]
```

## 依赖

| 依赖 | 说明 |
|------|------|
| `App\Contracts\Policy` | 策略抽象基类，实现 `getModelName()`、`getGroupName()`、`getPlatform()` |
| `App\Contracts\PolicyName` | PHP 8 属性注解，标注在策略的公共方法上 |
| `App\Enums\System\PolicyPlatform` | 平台枚举：`Backend`、`Tenant`、`Both`（位掩码） |

## 集成场景

- **TenantService::autoMakePermissions()** — 新建租户时自动分配所有权限
- **Filament RoleForm (Backend)** — 后台角色权限配置
- **Filament RoleForm (Tenant)** — 租户角色权限配置
