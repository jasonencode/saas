# 策略设计

## 策略类

每个主要模型对应一个策略类，位于 `app/Policies/` 目录（按业务模块分组）。策略类继承 `App\Contracts\Policy` 基类，通过 `#[UsePolicy]` 注解绑定到模型。

## 创建策略

```bash
php artisan make:policy ProductPolicy --model=Product
```

创建后修改为继承基类，并在模型上添加注解：

```php
// app/Models/Mall/Product.php
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use App\Policies\Mall\ProductPolicy;

#[UsePolicy(ProductPolicy::class)]
class Product extends Model
{
    // ...
}
```

## 策略结构

```php
<?php

namespace App\Policies\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Policy;
use App\Contracts\PolicyName;
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

    #[PolicyName('创建', type: PolicyType::Page)]
    public function create(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('更新', type: PolicyType::Page)]
    public function update(Authenticatable $user, Product $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('删除', type: PolicyType::Page)]
    public function delete(Authenticatable $user, Product $record): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('上架', type: PolicyType::Button)]
    public function up(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }

    #[PolicyName('下架', type: PolicyType::Button)]
    public function down(Authenticatable $user): bool
    {
        return $user->hasPermission(__CLASS__, __FUNCTION__);
    }
}
```

## 策略基类

`App\Contracts\Policy` 提供分组信息与超级管理员放行逻辑：

```php
abstract class Policy
{
    protected string $modelName = '鉴权';
    protected string $groupName = '系统权限';
    protected int $platform = PolicyPlatform::Both->value;

    /**
     * 超级管理员直接放行
     */
    public function before(User $user): ?bool
    {
        if ($user instanceof Administrator && $user->isAdministrator()) {
            return true;
        }

        return null;
    }
}
```

## 权限注解

使用 `#[PolicyName]`（`App\Contracts\PolicyName`）注解定义权限的显示名称与元信息：

```php
#[PolicyName('上架', type: PolicyType::Button)]
public function up(Authenticatable $user): bool
{
    return $user->hasPermission(__CLASS__, __FUNCTION__);
}
```

| 参数 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| `$policyName` | string | 必填 | 权限显示名称 |
| `$description` | ?string | `null` | 权限描述 |
| `$platform` | PolicyPlatform | `PolicyPlatform::Both` | 权限所属平台 |
| `$type` | PolicyType | `PolicyType::Button` | 权限类型（Page / Button） |

权限名称会被扫描沉淀到权限表，角色授权界面按 `groupName` / `modelName` 分组展示，实现按钮级授权。

## 使用方式

- Action 中：`userCan('up', $record)`（见 [权限系统](../core/permissions)）
- 页面中：`user()->can('viewAny', Product::class)`
- 控制器中：`$this->authorize('update', $record)` 或 `userCan()`
