# 策略设计

## 策略类

每个主要模型对应一个策略类，位于 `app/Policies/` 目录。

## 创建策略

```bash
php artisan make:policy ProductPolicy
```

## 策略结构

```php
<?php

namespace App\Policies\Mall;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    public function viewAny(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    public function view(Authenticatable $user, Product $product): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    public function create(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    public function update(Authenticatable $user, Product $product): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    public function delete(Authenticatable $user, Product $product): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('上架')]
    public function up(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('下架')]
    public function down(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
```

## 权限注解

使用 `#[PolicyName]` 注解定义权限的显示名称：

```php
#[PolicyName('上架')]
public function up(Authenticatable $user): bool
{
    return $user->hasPermission(__CLASS__, __FUNCTION__);
}
```
