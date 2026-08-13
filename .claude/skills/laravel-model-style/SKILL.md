---
name: laravel-model-style
description: Laravel Eloquent 模型代码风格规范，包括类结构、属性、关联方法、boot()、枚举使用等。
origin: USER
---

# Laravel Model 代码风格规范

适用于项目中所有 Eloquent Model 类。

## 适用场景

* 创建新的 Model 类
* 重构现有 Model 代码风格
* Review Model 代码时的检查标准

## 关联 Skill

* **数据表 / 迁移文件设计**：使用 `database-design`（迁移命名、表结构、字段类型、索引、外键约束、自定义宏）
* 本 Skill 负责 Eloquent 层（类结构、trait、关联、casts、枚举赋值），`database-design` 负责 Schema 层，两者字段命名与类型约定必须保持一致

## 规则

### 1. 类结构

每个 Model 必须使用以下 PHP 8 属性：

```php
#[Unguarded]
#[UsePolicy(XxxPolicy::class)]
class Xxx extends Model
{
    // ...
}
```

**可选属性（按需使用）：**

```php
// 单主键模型（如 UserAccount、UserRelation）
#[Table(key: 'user_id')]
#[WithoutIncrementing]

// 需要隐藏字段的模型（如 User）
#[Hidden(['password', 'remember_token'])]
```

### 2. Trait 声明

Trait 使用逗号分隔、每行一个的格式：

```php
use BelongsToTenant,
    BelongsToUser,
    HasEasyStatus,
    HasSortable,
    SoftDeletes;
```

**常用领域 Trait：**

| Trait | 用途 |
|-------|------|
| `BelongsToTenant` | 添加 `tenant()` 关联 + `ofTenant` Scope |
| `BelongsToUser` | 添加 `user()` 关联 + `ofUser` / `ofCurrentUser` Scope |
| `HasEasyStatus` | 添加 `enable()` / `disable()` / `toggleStatus()` + Scope |
| `HasSortable` | 添加排序 Scope |
| `AutoCreateOrderNo` | 自动生成订单号 |
| `SoftDeletes` | 软删除 |

### 3. 方法排序

Model 内部方法必须按以下顺序排列：

```
1. use Trait 声明（字母序）
2. 常量定义（const）
3. 类属性（$dispatchesEvents、$appends、$table 等）
4. casts()
5. boot() / getRouteKeyName() 等覆盖方法
6. 关联方法（relationships）
7. 访问器（getters）/ 修改器（setters）
8. 业务逻辑方法
```

### 4. boot() 方法

```php
protected static function boot(): void
{
    parent::boot();  // 必须第一行

    self::creating(static function (Xxx $model) {
        // 设置默认值
    });

    self::created(static function (Xxx $model) {
        // 触发后续操作
    });
}
```

**规则：**
- 返回类型必须是 `void`
- `parent::boot()` 必须第一行
- 回调使用 `static function`（非 `function`）
- 使用 `self::` 而非 `static::`（除非需要延迟静态绑定）
- 参数类型提示使用具体模型类名

### 5. 关联方法

**命名规则：**
- `belongsTo` / `hasOne` / `morphTo` → **单数**：`plan()`、`user()`、`target()`
- `hasMany` / `belongsToMany` / `morphMany` → **复数**：`tasks()`、`logs()`、`comments()`

**必须声明返回类型：**

```php
public function plan(): BelongsTo
public function tasks(): HasMany
public function profile(): HasOne
public function target(): MorphTo
public function tenants(): BelongsToMany
```

**PHPDoc 格式（中文 + 泛型）：**

```php
/**
 * 关联计划
 *
 * @return BelongsTo<Plan>
 */
public function plan(): BelongsTo
{
    return $this->belongsTo(Plan::class);
}

/**
 * 账户日志
 *
 * @return HasMany<UserAccountLog>
 */
public function logs(): HasMany
{
    return $this->hasMany(UserAccountLog::class, 'user_id');
}
```

### 6. casts() 方法

必须使用方法形式，禁止使用 `$casts` 属性：

```php
protected function casts(): array
{
    return [
        'status' => VoucherStatus::class,
        'amount' => 'decimal:2',
        'completed_at' => 'datetime',
        'options' => 'json',
    ];
}
```

### 7. 枚举使用

状态、类型等字段统一使用 Backed Enum，禁止使用原始值：

```php
// 正确
$voucher->status = VoucherStatus::Pending;

// 错误
$voucher->status = 'pending';
```

**casts 中使用 `::class`：**

```php
'status' => VoucherStatus::class,
'gateway' => PaymentGateway::class,
```

### 8. Import 顺序

按字母序排列，分两组：

```php
// 第一组：App 级别
use App\Contracts\SettlementTask;
use App\Enums\Finance\VoucherStatus;
use App\Models\Finance\Plan;
use App\Policies\Finance\VoucherPolicy;
use App\Support\Tasks\Traits\WithDefaultSetting;

// 第二组：框架/第三方
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
```

## 完整示例

```php
<?php

namespace App\Models\Finance;

use App\Enums\Finance\VoucherStatus;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use App\Policies\Finance\VoucherPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(VoucherPolicy::class)]
class Voucher extends Model
{
    use BelongsToTenant,
        BelongsToUser,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => VoucherStatus::class,
            'completed_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(static function (Voucher $voucher) {
            $voucher->status = VoucherStatus::Pending;
        });
    }

    /**
     * 关联计划
     *
     * @return BelongsTo<Plan>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * 关联目标模型
     *
     * @return MorphTo<Model>
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }
}
```

## 常见错误

### 使用 $casts 属性

```php
// 错误
protected $casts = [
    'status' => VoucherStatus::class,
];

// 正确
protected function casts(): array
{
    return [
        'status' => VoucherStatus::class,
    ];
}
```

### boot() 缺少 parent::boot()

```php
// 错误
protected static function boot(): void
{
    self::creating(static function (self $model) {
        //
    });
}

// 正确
protected static function boot(): void
{
    parent::boot();

    self::creating(static function (self $model) {
        //
    });
}
```

### 回调未使用 static function

```php
// 错误
self::creating(function (Voucher $voucher) {
    //
});

// 正确
self::creating(static function (Voucher $voucher) {
    //
});
```

### 关联缺少返回类型

```php
// 错误
public function plan()
{
    return $this->belongsTo(Plan::class);
}

// 正确
public function plan(): BelongsTo
{
    return $this->belongsTo(Plan::class);
}
```
