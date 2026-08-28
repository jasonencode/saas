# 权限系统

## 概述

Saas.Foundation 使用 Laravel Policy 实现基于策略的权限控制，支持页面级与按钮级（Action）两种权限粒度，并通过 `#[PolicyName]` 注解把权限名称沉淀到数据库，供角色授权界面使用。

## 策略注册

策略类继承 `App\Contracts\Policy` 基类，通过 `#[UsePolicy]` 注解绑定到模型（Laravel 属性自动注册，无需 `Gate::policy`）：

```php
#[UsePolicy(ProductPolicy::class)]
class Product extends Model
{
    // ...
}
```

### 策略基类

```php
// app/Contracts/Policy.php
abstract class Policy
{
    protected string $modelName = '鉴权';   // 权限分组内的模型名称
    protected string $groupName = '系统权限'; // 权限分组名称
    protected int $platform = PolicyPlatform::Both->value;

    /**
     * 是否放行权限检查
     */
    public function before(User $user): ?bool
    {
        // 超级管理员直接放行
        if ($user instanceof Administrator && $user->isAdministrator()) {
            return true;
        }

        return null;
    }
}
```

## 权限定义

### 目录结构

`app/Policies/` 下按业务模块分组（与 Models 目录对应）：

```
app/Policies/
├── BlockChain/   # CertificatePolicy、ChainAddressPolicy、ContractPolicy、NetworkPolicy ...
├── Campaign/     # CouponPolicy、LotteryPolicy、RedpackPolicy
├── Content/      # ContentPolicy、CategoryPolicy、CommentPolicy ...
├── Finance/      # UserAccountPolicy、PaymentOrderPolicy、InvoiceApplicationPolicy ...
├── Foundation/   # WechatPolicy、AlipayPolicy、SocialitePolicy ...
├── Mall/         # ProductPolicy、OrderPolicy、RefundPolicy ...
├── System/       # AdministratorPolicy、TenantPolicy、ApiLogPolicy ...
└── User/         # UserPolicy、AddressPolicy、UserRelationPolicy ...
```

### 定义权限方法

```php
<?php

namespace App\Policies\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
use App\Enums\System\PolicyPlatform;
use App\Enums\System\PolicyType;
use App\Models\Mall\Product;

class ProductPolicy extends Policy
{
    protected string $modelName = '全部商品';

    protected string $groupName = '商城管理';

    #[PolicyName('列表', type: PolicyType::Page)]
    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('详情', type: PolicyType::Page)]
    public function view(Authenticatable $user, Product $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('上架', type: PolicyType::Button)]
    public function up(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
```

### 权限注解

`#[PolicyName]`（`App\Contracts\PolicyName`）参数：

| 参数 | 类型 | 说明 |
|------|------|------|
| `$policyName` | string | 权限显示名称 |
| `$description` | ?string | 权限描述 |
| `$platform` | PolicyPlatform | 权限平台（Both / Web / Api 等） |
| `$type` | PolicyType | 权限类型（Page / Button） |

权限名称会被扫描后写入权限表，角色授权界面按 `groupName` + `modelName` 分组展示。

## 使用方式

### 在 Action 中使用

```php
use Filament\Actions\Action;
use function App\Support\userCan;

Action::make('up')
    ->visible(fn (Product $record) => userCan('up', $record) && $record->status === ProductStatus::Down)
    ->action(fn (Product $record) => service(ProductService::class)->up($record));
```

> 推荐使用 `visible()` 而非 `hidden()`：语义更明确（默认隐藏，需要明确授权才显示）。

### 在页面中使用

```php
public static function canAccess(): bool
{
    return user()->can('viewAny', Product::class);
}
```

## 辅助函数

### userCan()

```php
use function App\Support\userCan;

userCan(string $ability, string|Model $model): bool
```

检查当前用户是否拥有指定权限（`Auth::user()->can($ability, $model)`）。

### service()

```php
use function App\Support\service;

service(ProductService::class);
```

从容器获取服务实例，并校验其实现 `ServiceInterface`，否则抛出 `InvalidArgumentException`。

## 授权模型

| 角色 | 说明 |
|------|------|
| Administrator | 平台管理员，`isAdministrator()` 为真时策略基类 `before()` 直接放行 |
| 租户管理员 | 通过角色（`AdminRole` + `AdminRolePermission`）分配权限点 |
| 普通用户 | 通过 `user_tenant` 关联到租户，权限由所属角色决定 |
