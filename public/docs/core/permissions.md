# 权限系统

## 概述

JasonSaaS 使用 Laravel Policy 实现基于策略的权限控制。

## 权限定义

### Policy 目录结构

```
app/Policies/
├── Content/
│   └── CategoryPolicy.php
├── Mall/
│   ├── OrderPolicy.php
│   ├── ProductPolicy.php
│   └── ProductCategoryPolicy.php
└── User/
    ├── AddressPolicy.php
    └── UserRealnamePolicy.php
```

## 使用方式

### 定义权限方法

```php
<?php

namespace App\Policies\Mall;

use App\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('上架')]
    public function up(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
```

### 在 Action 中使用

```php
use Filament\Actions\Action;
use function App\Helpers\userCan;

Action::make('up')
    ->visible(fn (Product $record) => userCan('up', $record) && $record->status === ProductStatus::Down)
```

## 辅助函数

### userCan()

```php
userCan('methodName', $model = null): bool
```

检查当前用户是否拥有指定权限。

## 权限注解

使用 `#[PolicyName]` 注解定义权限显示名称：

```php
#[PolicyName('上架')]
public function up(Authenticatable $user): bool
{
    return $user->hasPermission(__CLASS__, __FUNCTION__);
}
```