---
name: phpdoc-style
description: PHPDoc 注释写法规则，包括方法说明、参数、返回值、异常声明的格式和顺序。
origin: USER
---

# PHPDoc 注释写法规则

适用于项目中所有 PHP 类的方法注释。

## 适用场景

* 为 public/protected 方法添加 PHPDoc 注释
* 统一注释格式和风格
* 确保参数、返回值和异常声明完整

## 规则

### 1. 方法说明

方法说明放在第一行，简洁描述方法功能。

**Service 方法：** 使用动词开头，描述操作
```php
/**
 * 创建订单
 */
public function createOrder(): Order
```

**Model 关联：** 使用 "关联XXX" 格式
```php
/**
 * 关联品牌
 */
public function brand(): BelongsTo
```

**Model Scope：** 使用描述性短语
```php
/**
 * 已启用的记录
 */
#[Scope]
protected function ofEnabled(Builder $query): void
```

**判断方法：** 使用 "是否XXX" 格式
```php
/**
 * 是否成年
 */
public function isAdult(): bool
```

### 2. 参数格式

每个参数使用 `@param` 标签，格式：`@param  类型  $名称  说明`

```php
/**
 * @param  Tenant  $tenant  所属租户
 * @param  Authenticatable  $user  下单用户
 * @param  Collection|array  $items  订单商品列表（OrderItemDto 数组）
 * @param  Address|int|null  $address  收货地址（地址对象、地址 ID 或 null）
 * @param  string|null  $remark  订单备注
 */
```

**参数说明写法：**
- 简单参数：直接说明用途 `@param  User  $user  用户`
- 复杂参数：补充说明格式或类型 `@param  Collection  $items  订单商品列表（OrderItemDto 数组）`
- 可选参数：说明可选值 `@param  Address|int|null  $address  收货地址（地址对象、地址 ID 或 null）`

### 3. 返回值格式

使用 `@return` 标签，格式：`@return  类型  说明`

**简单类型：**
```php
/**
 * @return bool  是否发送成功
 */
```

**Model 对象：**
```php
/**
 * @return Order  创建的订单
 */
```

**关联方法（使用泛型）：**
```php
/**
 * @return BelongsTo<Brand>
 */
public function brand(): BelongsTo

/**
 * @return HasMany<UserAccountLog>
 */
public function logs(): HasMany

/**
 * @return HasOneThrough<Tenant>
 */
public function tenant(): HasOneThrough
```

**集合类型（使用泛型）：**
```php
/**
 * @return Collection<int, Order>  订单列表
 */
```

**数组类型（使用数组形状）：**
```php
/**
 * @return array{uuid: string, name: string, size: int, url: string, path: string}  文件信息
 */
```

### 4. 异常声明格式

使用 `@throws` 标签，格式：`@throws  异常类型  触发条件`

```php
/**
 * @throws InvalidArgumentException  商品列表为空或商品类型错误
 * @throws RuntimeException  地址不存在
 * @throws Throwable  事务异常
 */
```

**异常说明写法：**
- 明确触发条件 `@throws  InvalidArgumentException  商品列表为空`
- 通用异常说明 `@throws  Throwable  事务异常`

### 5. 注释顺序

PHPDoc 注释应按以下顺序排列：

```php
/**
 * 方法说明（第一行）
 *
 * @param  类型  $参数1  说明1
 * @param  类型  $参数2  说明2
 *
 * @return  类型  说明
 *
 * @throws  异常类型  触发条件
 */
```

**顺序说明：**
1. 方法说明
2. 空行
3. `@param` 标签（按参数顺序）
4. 空行
5. `@return` 标签
6. 空行
7. `@throws` 标签

## 完整示例

### Service 方法

```php
/**
 * 创建订单
 *
 * @param  Tenant  $tenant  所属租户
 * @param  Authenticatable  $user  下单用户
 * @param  Collection|array  $items  订单商品列表（OrderItemDto 数组）
 * @param  Address|int|null  $address  收货地址（地址对象、地址 ID 或 null）
 * @param  string|null  $remark  订单备注
 *
 * @return Order  创建的订单
 *
 * @throws InvalidArgumentException  商品列表为空或商品类型错误
 * @throws RuntimeException  地址不存在
 */
public function createOrder(Tenant $tenant, Authenticatable $user, Collection|array $items, Address|int|null $address = null, ?string $remark = null): Order
```

### Model 关联

**BelongsTo：**
```php
/**
 * 关联品牌
 *
 * @return BelongsTo<Brand>
 */
public function brand(): BelongsTo
{
    return $this->belongsTo(Brand::class);
}
```

**HasMany：**
```php
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

**HasOneThrough：**
```php
/**
 * 关联租户
 *
 * @return HasOneThrough<Tenant>
 */
public function tenant(): HasOneThrough
{
    return $this->hasOneThrough(
        Tenant::class,
        User::class,
        'id', // users.id
        'id', // tenants.id
        'user_id', // user_accounts.user_id
        'tenant_id' // users.tenant_id
    );
}
```

**BelongsToMany：**
```php
/**
 * 关联角色
 *
 * @return BelongsToMany<Role>
 */
public function roles(): BelongsToMany
{
    return $this->belongsToMany(Role::class);
}
```

**MorphTo：**
```php
/**
 * 评论所属模型
 *
 * @return MorphTo<Model>
 */
public function commentable(): MorphTo
{
    return $this->morphTo();
}
```

**MorphMany：**
```php
/**
 * 关联评论
 *
 * @return MorphMany<Comment>
 */
public function comments(): MorphMany
{
    return $this->morphMany(Comment::class, 'commentable');
}
```

### Model Scope

```php
/**
 * 已启用的记录
 */
#[Scope]
protected function ofEnabled(Builder $query): void
{
    $query->where('status', true);
}
```

### Model 访问器

```php
/**
 * 获取全名
 */
public function getFullNameAttribute(): string
{
    return "{$this->first_name} {$this->last_name}";
}
```

### Model 修改器

```php
/**
 * 设置昵称（敏感词过滤）
 */
public function setNicknameAttribute(string $value): void
{
    $this->attributes['nickname'] = app(SensitiveService::class)->filter($value);
}
```

### 简单方法

```php
/**
 * 是否成年
 *
 * @return bool  是否成年
 */
public function isAdult(): bool
{
    return $this->age >= 18;
}
```

### 文件上传

```php
/**
 * 保存文件
 *
 * @param  UploadedFile  $file  上传的文件
 *
 * @return array{uuid: string, name: string, size: int, url: string, path: string}  文件信息
 *
 * @throws RuntimeException  文件上传失败
 */
public function save(UploadedFile $file): array
```

## 常见错误

### 错误顺序

```php
// 错误：@return 在 @param 之前
/**
 * @return Order  订单
 * @param  int  $id  订单 ID
 */
```

### 缺少说明

```php
// 错误：参数缺少说明
/**
 * @param  int  $id
 * @param  string  $name
 */

// 正确：参数有说明
/**
 * @param  int  $id  订单 ID
 * @param  string  $name  订单名称
 */
```

### 缺少返回值

```php
// 错误：没有 @return
/**
 * 创建订单
 * @param  array  $data  订单数据
 */

// 正确：有 @return
/**
 * 创建订单
 *
 * @param  array  $data  订单数据
 *
 * @return Order  创建的订单
 */
```

### 关联缺少泛型

```php
// 错误：关联没有泛型
/**
 * 关联品牌
 * @return BelongsTo
 */

// 正确：关联有泛型
/**
 * 关联品牌
 *
 * @return BelongsTo<Brand>
 */
```
