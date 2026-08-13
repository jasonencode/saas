---
name: database-design
description: 数据库设计规范，涵盖迁移文件命名、表结构、字段类型、索引策略、外键约束与性能优化。用于创建或修改迁移文件、设计数据表结构、评审数据库设计。
origin: USER
---

# 数据库设计规范

适用于项目中所有数据表的设计与迁移文件编写。

## 适用场景

* 创建新的数据表 / 迁移文件
* 修改已有表结构（新增字段、索引、约束）
* 设计表关系（1:1、1:N、N:N、多态）
* 评审数据库设计的命名、类型与索引合理性

## 关联 Skill

* **数据表对应的 Model 代码风格**：使用 `laravel-model-style`（类结构、trait、关联方法、casts、枚举赋值等）
* 本 Skill 负责 Schema 层（表/字段/索引/约束）设计，`laravel-model-style` 负责 Eloquent 层，两者字段命名与类型约定必须保持一致

## 迁移文件

### 命名规则

格式：`{模块前缀}_{序号}_{操作}_{表名}.php`

```
0001_00_00_000002_create_users_table.php
0001_00_00_000003_add_path_prefix_index_to_user_relations_table.php
0003_02_00_000001_create_orders_table.php
```

* **模块前缀**：`0001_00_00` 表示模块/领域（用户域 `0001`、内容域 `0002`、商城域 `0003`、财务域 `0004` 等），同领域迁移共享前缀，序号依次递增
* **操作动词**：`create` / `add` / `alter` / `drop` / `rename`
* **表名**：复数蛇形命名（`user_relations`、`user_profiles`、`order_items`）
* 同一模块内多张关联表可合并到一个迁移文件中（如 `create_orders_table.php` 内含 orders/order_items/order_logs/order_shippings/order_addresses），`down()` 中按依赖倒序 `dropIfExists`

### 基本结构

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', static function (Blueprint $table) {
            $table->comment('订单主表');
            $table->id();
            // ...字段定义
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
```

## 项目自定义宏

项目中已通过 `AppServiceProvider::bootBluePrint()` 注册常用字段宏，**优先使用宏**，避免重复手写：

```php
$table->tenant();        // tenant_id 可空+索引+注释
$table->user();          // user_id 索引+注释（注意：不是主键）
$table->no();            // 单号 string(32) 唯一
$table->cover();         // 封面 string 可空
$table->pictures();      // 轮播图 json 可空
$table->easyStatus();    // 布尔状态 status，默认 false + 索引
$table->sort();          // 排序 integer + 索引
$table->regionAddress(); // 省/市/区三级 + 详细地址（4 个字段）
```

宏定义（`app/Providers/AppServiceProvider.php`）：

| 宏 | 生成字段 | 说明 |
|---|---|---|
| `tenant()` | `tenant_id` | 可空 + 索引，`所属租户` |
| `user()` | `user_id` | 索引，`所属用户` |
| `no()` | `no` (string 32) | 唯一，`订单编号` |
| `cover()` | `cover` | 可空，`封面图片` |
| `pictures()` | `pictures` (json) | 可空，`轮播图` |
| `easyStatus()` | `status` (boolean) | 默认 false + 索引 |
| `sort()` | `sort` (integer) | 默认 0 + 索引 |
| `regionAddress()` | `province_id`/`city_id`/`district_id`/`address` | 地址四字段 |

## 表结构规则

### 必备字段

* **主键**：一律使用 `$table->id()`（简单中间表除外，见下方 N:N）
* **时间戳**：业务表必须 `$table->timestamps()`
* **软删除**：需要保留数据的表使用 `$table->softDeletes()->index()`
* **表注释**：每个表必须 `$table->comment('表说明')`
* **多租户**：涉及租户数据的表用 `$table->tenant()`
* **所属用户**：涉及用户的表用 `$table->user()`

### 字段命名

* 蛇形命名（`created_at`、`frozen_balance`）
* 外键字段用单数关联名 + `_id`（`parent_id`、`category_id`、`order_id`）
* 布尔字段用 `is_` / `has_` 前缀（`is_used`、`is_default`）
* 计数字段用 `{entity}_count`（`direct_count`、`team_count`）
* 排序字段统一用 `sort` 宏（数字越大越靠前）

### 字段类型

| 场景 | 类型 | 说明 |
|---|---|---|
| 主键/外键 | `id()` / `unsignedBigInteger()` | 大整数无符号 |
| 短文本 | `string('name')` | 默认 255 |
| 长文本 | `text()` / `longText()` | 描述、正文等 |
| 全文检索 | `string(...)->fullText()` | 标题、正文、备注等可搜索字段 |
| 整数 | `unsignedInteger()` / `integer()` | 计数、排序（计数无符号） |
| 金额 | `decimal('amount', 12)->unsigned()` | **必须 decimal(12) 无符号**，禁止 float |
| 枚举语义 | `string('status', 16)->default(Enum::case->value)` | 字符串 + 应用层 Enum，不用 int |
| 布尔 | `boolean('is_used')->default(false)` | 或 `easyStatus()` 宏 |
| 日期 | `date('birthday')` / `dateTime('start_at')` | 纯日期用 date，时刻用 dateTime/timestamp |
| JSON | `json('pictures')` / `jsonb('context')` | 结构化数据 |
| IP | `ipAddress('ip')` | 支持 IPv4/IPv6 |
| 多态 | `morphs('user')` / `nullableMorphs('operator')` | 或手动 `{type}_type` + `{type}_id` |

### 注释要求

每个字段必须 `->comment('字段说明')`，说明简洁描述业务含义：

```php
$table->unsignedTinyInteger('layer')
    ->default(1)
    ->index()
    ->comment('所在层级');
```

## 索引策略

### 必建索引

* 外键字段（`category_id`、`order_id` 等，均 `->index()`）
* 高频查询字段（`status`、`tenant_id`、`created_at`）
* 排序字段（`created_at`、`sort`）
* 唯一约束用 `unique()` 而非普通索引（`no`、`code`、`token` 等）

### 组合索引

* 多条件查询建组合索引，遵循**最左前缀**原则，项目常见三列组合：

```php
$table->index(['tenant_id', 'status', 'created_at']);
$table->index(['user_id', 'status', 'created_at']);
$table->index(['tenant_id', 'status', 'sort']);
```

* 过滤性强的字段放前面（`tenant_id` / `user_id` 在前）
* 单列高频排序再补 `$table->index('created_at')`

### 索引注意事项

* 禁止为低频查询字段建冗余索引（写放大）
* `text` 类型不能直接建普通索引，需前缀索引（PostgreSQL）：

```php
if (DB::connection()->getDriverName() !== 'pgsql') {
    return;
}
DB::statement('CREATE INDEX idx_user_relations_path_prefix ON user_relations (path text_pattern_ops)');
```

## 外键约束

### 1:1 关系

```php
// 主表 + 扩展表：扩展表用 user() 宏（user_id 索引，非主键）
Schema::create('user_profiles', static function (Blueprint $table) {
    $table->comment('用户扩展信息');
    $table->user();
    // ...
});
```

### 1:N 关系

外键字段用 `unsignedBigInteger` + 索引，**项目普遍不写 `constrained()`**（避免级联锁），由应用层保证完整性：

```php
$table->unsignedBigInteger('order_id')
    ->index()
    ->comment('订单ID');
```

**仅在需要数据库级级联删除时**使用 `foreignId()->constrained()`：

```php
$table->foreignId('parent_id')
    ->nullable()
    ->index()
    ->comment('直接推荐人ID')
    ->constrained('users')
    ->cascadeOnDelete();
```

### N:N 关系（中间表）

```php
Schema::create('user_tenant', static function (Blueprint $table) {
    $table->comment('用户租户关联表');
    $table->id();
    $table->unsignedBigInteger('user_id')->index();
    $table->unsignedBigInteger('tenant_id')->index();
    $table->timestamps();
    $table->unique(['user_id', 'tenant_id']);
});
```

**简单关联中间表可直接用复合主键，省略 `id()`**：

```php
Schema::create('coupon_product', static function (Blueprint $table) {
    $table->comment('优惠券适用商品关联表');
    $table->unsignedBigInteger('coupon_id')->comment('优惠券ID');
    $table->unsignedBigInteger('product_id')->comment('商品ID');
    $table->timestamps();
    $table->primary(['coupon_id', 'product_id']);
});
```

### 约束规则

* 关联字段可空时（如 `parent_id`、`order_shipping_id`）必须 `->nullable()`
* 中间表必须防重复：复合主键 `primary([...])` 或 `unique([...])` 二选一
* 删除策略：`cascadeOnDelete()`（从属数据）/ `nullOnDelete()`（历史保留）

## 性能与容量

* 预估超千万行的表，禁止在 `text` 字段上建索引
* 大表加字段尽量用单条迁移，避免长事务
* 冗余计数字段（如 `direct_count`、`team_count`）需配合事务维护，避免用 `COUNT(*)` 实时统计
* 分层结构（树）用 `path` 前缀匹配 + 前缀索引，替代递归查询
* 全文检索字段用 `fullText()` 索引，而非 `LIKE '%...%'`

## 完整示例

```php
Schema::create('coupons', static function (Blueprint $table) {
    $table->comment('优惠券定义表');
    $table->id();
    $table->tenant();
    $table->string('name')->comment('优惠券名称');
    $table->string('code', 64)->unique()->comment('优惠券代码，唯一');
    $table->decimal('value', 12)->unsigned()->comment('折扣值');
    $table->decimal('min_amount', 12)->unsigned()->nullable()->comment('最低消费金额，可选');
    $table->string('type', 64)->index()->comment('优惠券类型');
    $table->string('expired_type', 64)->index()->comment('过期类型');
    $table->integer('days')->default(0)->comment('有效期（天），为0永不过期');
    $table->easyStatus();
    $table->timestamps();
    $table->softDeletes();
    $table->index(['tenant_id', 'status', 'created_at']);
    $table->index('created_at');
});
```
