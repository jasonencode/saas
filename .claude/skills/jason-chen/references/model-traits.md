# Laravel Model Traits 规范

## 1. 概述

Traits 用于提取和复用 Eloquent 模型的公共行为。通过 Traits，我们可以将模型的关联、作用域、方法等可复用的部分抽取出来，在多个模型间共享。

## 2. Traits 分类

Traits 按职责分为三类：

| 类型         | 描述                        | 示例                                      |
| ------------ | --------------------------- | ---------------------------------------- |
| **关联型**   | 添加模型关联和设置器        | BelongsToUser, MorphToUser, HasComments |
| **作用域型** | 添加查询作用域（含功能方法）| HasSortable, HasEasyStatus, HasRegion    |
| **功能型**   | 添加复杂业务功能            | HasCovers, AutoCreateOrderNo             |

## 3. 命名规范

```
{特征}{类型}
```

### 3.1 关联型

```
BelongsTo{Model}, MorphTo{Model}
```

- `BelongsToUser` - 属于用户关联
- `BelongsToTenant` - 属于租户关联
- `MorphToUser` - 多态用户关联
- `HasComments` - 拥有评论关联

### 3.2 作用域型

```
Has{Feature}
```

- `HasSortable` - 可排序作用域
- `HasEasyStatus` - 状态管理作用域
- `HasRegion` - 地区作用域

### 3.3 功能型

```
Has{Feature}, Auto{Action}{Entity}
```

- `HasEasyStatus` - 简易状态管理
- `HasCovers` - 封面图片处理
- `AutoCreateOrderNo` - 自动创建订单号

## 4. 文件结构

```
app/Models/Traits/
├── BelongsToUser.php           # 关联型
├── BelongsToTenant.php         # 关联型
├── MorphToUser.php             # 关联型
├── HasComments.php             # 关联型
├── HasSortable.php             # 作用域型
├── HasEasyStatus.php           # 作用域型 + 功能型
├── HasCovers.php               # 功能型
├── HasRegion.php               # 作用域型 + 功能型
└── AutoCreateOrderNo.php       # 功能型
```

> 注：`OrderScopes`、`RefundScopes`、`ProductScopes` 等旧的Scopes后缀命名已废弃，统一使用 `Has{Feature}` 模式。

## 5. 代码规范

### 5.1 必需元素

每个 Trait 必须包含完整的文档注释，说明期望的模型属性和作用域方法：

```php
<?php

namespace App\Models\Traits;

use App\Models\User\User;use Illuminate\Database\Eloquent\Builder;

/**
 * 用户关联模型特征
 *
 * @property int $user_id
 *
 * @method Builder ofUser(User $user)
 * @method Builder ofCurrentUser()
 */
trait BelongsToUser
{
    // Trait 内容
}
```

### 5.2 关联型 Traits

关联型 Traits 提供模型关联关系和属性设置器：

```php
<?php

namespace App\Models\Traits;

use App\Models\User\User;use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 用户关联模型特征
 *
 * @property int $user_id
 *
 * @method Builder ofUser(User $user)
 */
trait BelongsToUser
{
    /**
     * 设置关联用户
     *
     * @param  User  $user
     * @return void
     */
    public function setUserAttribute(User $user): void
    {
        $this->attributes['user_id'] = $user->getKey();
    }

    /**
     * 关联用户
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)
            ->withoutGlobalScopes();
    }
}
```

### 5.3 作用域型 Traits

使用 PHP 8 的 `#[Scope]` 属性定义查询作用域：

```php
<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * 排序特征
 *
 * @property int $sort
 *
 * @method Builder bySort()
 */
trait HasSortable
{
    /**
     * 排序作用域
     *
     * @param  Builder  $query
     * @return void
     */
    #[Scope]
    protected function bySort(Builder $query): void
    {
        $query->orderByDesc('sort')->latest();
    }
}
```

### 5.4 功能型 Traits

使用可配置属性支持字段名自定义，提高灵活性：

```php
<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * 简易状态特征
 *
 * @property bool $status
 */
trait HasEasyStatus
{
    protected ?string $statusField = null;

    protected function getStatusField(): string
    {
        return $this->statusField ?? 'status';
    }

    public function toggleStatus(): bool
    {
        return $this->isEnabled() ? $this->disable() : $this->enable();
    }

    public function isEnabled(): bool
    {
        return (bool) $this->{$this->getStatusField()};
    }

    public function disable(): bool
    {
        $this->{$this->getStatusField()} = false;

        return $this->save();
    }

    public function enable(): bool
    {
        $this->{$this->getStatusField()} = true;

        return $this->save();
    }

    #[Scope]
    protected function ofEnabled(Builder $query): Builder
    {
        return $query->where($this->getStatusField(), true);
    }

    #[Scope]
    protected function ofDisabled(Builder $query): Builder
    {
        return $query->where($this->getStatusField(), false);
    }
}
```

### 5.5 Bootable Traits

使用 `boot{TraitName}` 模式响应模型生命周期事件：

```php
<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Throwable;

/**
 * 自动生成订单号特征
 *
 * @property string $no
 */
trait AutoCreateOrderNo
{
    protected static function bootAutoCreateOrderNo(): void
    {
        static::creating(static function (Model $model) {
            do {
                $orderNo = static::generateOrderNo($model);
                $exists = static::where(static::getOrderNoField($model), $orderNo)->exists();
            } while ($exists);

            $model->{static::getOrderNoField($model)} = $orderNo;
        });
    }

    protected static function generateOrderNo(Model $model): string
    {
        try {
            $time = explode(' ', microtime());
            $no = date('ymdHis').sprintf('%05d', $time[0] * 1e5);

            return static::getOrderNoPrefix($model).Sigma::orderNo($no);
        } catch (Throwable $e) {
            throw new RuntimeException(
                "生成订单号失败：{$e->getMessage()}"
            );
        }
    }

    protected static function getOrderNoPrefix(Model $model): string
    {
        return property_exists($model, 'orderNoPrefix') ? $model->orderNoPrefix : '';
    }

    protected static function getOrderNoField(Model $model): string
    {
        return property_exists($model, 'orderNoField') ? $model->orderNoField : 'no';
    }
}
```

## 6. 初始化方法

使用 `initialize{TraitName}` 模式进行模型初始化配置，如字段类型转换：

```php
<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * 封面关联模型特征
 *
 * @property string $cover
 * @property array $pictures
 */
trait HasCovers
{
    protected ?string $picturesField = null;
    protected ?string $coverField = null;
    protected ?string $avatarField = null;
    protected ?string $defaultImage = null;

    public function initializeHasCovers(): void
    {
        $this->mergeCasts([
            $this->getPicturesField() => 'array',
            $this->getCoverField() => 'string',
            $this->getAvatarField() => 'string',
        ]);
    }

    protected function getPicturesField(): string
    {
        return $this->picturesField ?? 'pictures';
    }

    protected function getCoverField(): string
    {
        return $this->coverField ?? 'cover';
    }

    protected function getAvatarField(): string
    {
        return $this->avatarField ?? 'avatar';
    }

    protected function parseImageUrl(?string $image): ?string
    {
        if (empty($image)) {
            return $this->getDefaultImage();
        }

        if (Str::startsWith($image, ['http://', 'https://', '//'])) {
            return $image;
        }

        if (Str::startsWith($image, '/')) {
            return asset($image);
        }

        return Storage::url($image);
    }

    protected function getDefaultImage(): ?string
    {
        return $this->defaultImage ?? null;
    }

    public function avatarUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->parseImageUrl($this->getAttribute($this->getAvatarField()))
        )->shouldCache();
    }
}
```

## 7. Traits 组合使用

模型可以同时使用多个 Traits，通过可配置属性实现灵活定制：

```php
<?php

namespace App\Models;

use App\Models\Traits\BelongsToUser;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use App\Models\Traits\AutoCreateOrderNo;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use AutoCreateOrderNo,
        BelongsToUser,
        BelongsToTenant,
        HasEasyStatus,
        HasSortable,
        HasCovers;

    protected ?string $statusField = 'status';
    public string $orderNoPrefix = 'ORD';
    protected ?string $orderNoField = 'no';

    protected static function boot(): void
    {
        parent::boot();
        static::bootAutoCreateOrderNo();
    }
}
```

## 8. 使用注意事项

### 8.1 避免 Trait 冲突

如果多个 Trait 定义了相同方法名，使用 `insteadof` 操作符解决：

```php
trait A
{
    public function helper() { /* ... */ }
}

trait B
{
    public function helper() { /* ... */ }
}

class MyModel
{
    use A, B {
        B::helper insteadof A;  // 使用 B 的 helper
        A::helper as helperA;   // 别名访问 A 的 helper
    }
}
```

### 8.2 保持 Trait 独立

Trait 应该是自包含的，不依赖其他 Traits。如果需要共享逻辑，考虑：

- 提取为独立的方法
- 使用组合模式
- 改为使用服务类

### 8.3 文档化属性

使用 `@property` PHPDoc 注释声明 Trait 期望的模型属性：

```php
/**
 * 用户关联模型特征
 *
 * @property int $user_id
 * @property string $user_type
 */
trait MorphToUser
```

### 8.4 显式依赖

在 Trait 中明确 `use` 需要引用的类：

```php


```

### 8.5 测试 Trait

单独测试 Trait 的行为，确保：

- 每个 Trait 可以独立工作
- 多个 Traits 组合使用时没有冲突
- 可配置属性按预期工作

## 9. 判定标准

| 场景                         | 使用 Trait | 替代方案         |
| ---------------------------- | ---------- | ---------------- |
| 多个模型有相同的关联关系     | ✅         | -                |
| 多个模型有相同的状态管理逻辑 | ✅         | 枚举 + 单独方法  |
| 需要响应模型生命周期事件     | ✅         | 模型观察器       |
| 业务逻辑高度复杂             | ❌         | 服务类           |
| 只需要一个方法               | ❌         | 直接在模型中定义 |

### 9.1 何时使用 Trait

- 当多个不相关的模型需要共享相同的行为时
- 当行为涉及模型关联、查询作用域、属性设置等 Eloquent 相关功能时
- 当行为需要通过 Traits 组合实现灵活定制时

### 9.2 何时不使用 Trait

- 当业务逻辑非常复杂，涉及多个领域逻辑时 → 使用服务类
- 当只需要在一个模型中使用时 → 直接在模型中定义
- 当行为可以简单地通过继承实现时 → 考虑使用抽象类

---

**版本**：1.0.0
**最后更新**：2026-04-24
