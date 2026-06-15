# 商品模型与订单模型关联分析

> 分析时间：2026-06-15
> 分析范围：Product、Sku、Order、OrderItem 模型

---

## 一、模型关系概览

```
Product (商品)
  ├── skus (HasMany → Sku)
  ├── brand (BelongsTo → Brand)
  ├── category (BelongsTo → ProductCategory)
  └── delivery (BelongsTo → Delivery)

Sku (商品规格)
  └── product (BelongsTo → Product)

Order (订单)
  ├── items (HasMany → OrderItem)
  ├── address (HasOne → OrderAddress)
  ├── shippings (HasMany → OrderShipping)
  ├── refunds (HasMany → Refund)
  └── logs (HasMany → OrderLog)

OrderItem (订单明细)
  ├── order (BelongsTo → Order)
  ├── product (BelongsTo → Product, withTrashed)
  ├── sku (BelongsTo → Sku) ⚠️ 缺少 withTrashed
  ├── refundItem (HasOne → RefundItem)
  └── orderShipping (BelongsTo → OrderShipping)
```

---

## 二、发现的问题

### 🔴 高优先级

#### 1. Sku 模型缺少 `SoftDeletes` trait

**问题描述：**

- 数据库迁移 `0003_01_00_000001_create_products_table.php` 中 `skus` 表已添加 `softDeletes`
- 但 `app/Models/Mall/Sku.php` 模型**没有使用** `SoftDeletes` trait

**影响：**

- 无法对 SKU 进行软删除操作
- `Sku::query()->withTrashed()` 等方法不可用

**修复方案：**

```php
// app/Models/Mall/Sku.php
use Illuminate\Database\Eloquent\SoftDeletes;

class Sku extends Model
{
    use SoftDeletes;
    // ...
}
```

---

#### 2. OrderItem 关联 Sku 缺少 `withTrashed()`

**问题描述：**

- `OrderItem->product()` 使用了 `->withTrashed()`，正确处理了商品被删除的情况
- 但 `OrderItem->sku()` **没有使用** `->withTrashed()`

**影响：**

- 当 SKU 被软删除后，订单明细将无法正确关联到 SKU 数据
- 订单详情页可能显示空白的规格信息

**修复方案：**

```php
// app/Models/Mall/OrderItem.php
public function sku(): BelongsTo
{
    return $this->belongsTo(Sku::class)
        ->withTrashed();
}
```

---

### 🟡 中优先级

#### 3. OrderItem 缺少商品快照字段

**问题描述：**
当前 `order_items` 表只有以下快照字段：

- `product_name` - 商品名称
- `sku_name` - 规格名称
- `price` - 单价

**缺失的快照字段：**

- `cover` - 商品/规格封面图（用于订单列表展示）
- `origin_price` - 原价（用于展示划线价）

**影响：**

- 订单列表无法直接显示商品图片，需要额外查询
- 无法展示原价对比（划线价效果）

**修复方案：**

```php
// 新建迁移文件
Schema::table('order_items', function (Blueprint $table) {
    $table->string('cover')->nullable()->after('sku_name')->comment('商品封面快照');
    $table->decimal('origin_price', 10)->unsigned()->nullable()->after('price')->comment('原价快照');
});
```

---

#### 4. Product 访问器字段名与数据库字段不一致

**问题描述：**
| 访问器方法 | 返回字段名 | 数据库实际字段 |
|-----------|-----------|---------------|
| `getStocksAttribute()` | `stocks` | `stock` (在 skus 表) |
| `getSalesAttribute()` | `sales` | `sale` (在 skus 表) |

**影响：**

- 这是**设计意图**：Product 的 `stocks` 和 `sales` 是从所有 SKU 汇总的聚合值
- 但命名可能导致开发者混淆

**建议：**

- 保持现状，但在注释中明确说明这是聚合字段
- 或者考虑重命名为 `total_stock` 和 `total_sale` 以区分

---

### 🟢 低优先级

#### 5. Sku 模型缺少字段 cast

**问题描述：**
Sku 模型当前的 casts：

```php
protected $casts = [
    'origin_price' => 'decimal:2',
    'price' => 'decimal:2',
];
```

**缺少的 cast：**

- `stock` → `integer`
- `sale` → `integer`
- `sort` → `integer`

**修复方案：**

```php
protected $casts = [
    'origin_price' => 'decimal:2',
    'price' => 'decimal:2',
    'stock' => 'integer',
    'sale' => 'integer',
    'sort' => 'integer',
];
```

---

#### 6. Product 的 `$appends` 配置

**问题描述：**
当前 `$appends` 只有：

```php
protected $appends = [
    'delivery_template',
];
```

以下访问器**未 append**：

- `price` - 价格区间
- `origin_price` - 原价区间
- `stocks` - 总库存
- `sales` - 总销量

**影响：**

- 这些字段在 API 响应中默认不返回
- 需要前端显式请求或后端手动 append

**建议：**

- 如果这些字段在商品列表/详情 API 中常用，建议添加到 `$appends`
- 如果仅在 Filament 后台使用，保持现状即可

---

## 三、数据库字段对照表

### products 表

| 字段                  | 类型      | 说明     | 访问器               |
|---------------------|---------|--------|-------------------|
| `id`                | bigint  | 主键     | -                 |
| `tenant_id`         | bigint  | 租户ID   | -                 |
| `name`              | string  | 商品名称   | -                 |
| `description`       | text    | 商品简介   | -                 |
| `cover`             | string  | 封面图    | HasCovers trait   |
| `pictures`          | json    | 轮播图    | HasCovers trait   |
| `category_id`       | bigint  | 分类ID   | -                 |
| `brand_id`          | bigint  | 品牌ID   | -                 |
| `delivery_id`       | bigint  | 运费模板ID | -                 |
| `deduct_stock_type` | string  | 库存扣减方式 | Enum cast         |
| `can_cart`          | boolean | 可加入购物车 | bool cast         |
| `status`            | string  | 商品状态   | Enum cast         |
| `sort`              | integer | 排序     | HasSortable trait |
| `materials`         | json    | 详情图片集  | json cast         |
| `ext`               | json    | 扩展信息   | json cast         |
| `views`             | bigint  | 浏览量    | -                 |
| `weight`            | decimal | 重量     | decimal:2 cast    |
| `volume`            | decimal | 体积     | decimal:2 cast    |

### skus 表

| 字段             | 类型        | 说明    | 备注             |
|----------------|-----------|-------|----------------|
| `id`           | bigint    | 主键    | -              |
| `product_id`   | bigint    | 商品ID  | -              |
| `name`         | string    | 规格名称  | 如：红色/L         |
| `code`         | string    | 规格编号  | 69码            |
| `cover`        | string    | 规格封面图 | **新增**         |
| `origin_price` | decimal   | 原价    | decimal:2 cast |
| `price`        | decimal   | 销售价   | decimal:2 cast |
| `stock`        | integer   | 库存    | -              |
| `sale`         | integer   | 销量    | -              |
| `sort`         | integer   | 排序    | **新增**         |
| `deleted_at`   | timestamp | 软删除   | **新增，模型未启用**   |

### order_items 表

| 字段                  | 类型      | 说明     | 备注             |
|---------------------|---------|--------|----------------|
| `id`                | bigint  | 主键     | -              |
| `order_id`          | bigint  | 订单ID   | -              |
| `order_shipping_id` | bigint  | 物流ID   | -              |
| `product_id`        | bigint  | 商品ID   | -              |
| `sku_id`            | bigint  | SKU ID | -              |
| `product_name`      | string  | 商品名称快照 | -              |
| `sku_name`          | string  | 规格名称快照 | -              |
| `qty`               | integer | 购买数量   | -              |
| `price`             | decimal | 商品单价   | decimal:2 cast |
| `remark`            | string  | 商品备注   | -              |

---

## 四、修复优先级清单

| 序号 | 问题                             | 优先级  | 影响范围      | 状态 |
|----|--------------------------------|------|-----------|------|
| 1  | Sku 模型添加 SoftDeletes           | 🔴 高 | SKU 软删除功能 | ✅ 已完成 |
| 2  | OrderItem.sku() 添加 withTrashed | 🔴 高 | 订单详情显示    | ✅ 已完成 |
| 3  | order_items 添加 cover 字段        | 🟡 中 | 订单列表展示    | ✅ 已完成 |
| 4  | order_items 添加 origin_price 字段 | 🟡 中 | 划线价展示     | ⏭️ 跳过 |
| 5  | Sku 添加缺失的 casts                | 🟢 低 | 数据类型一致性   | ✅ 已完成 |
| 6  | Product $appends 配置            | 🟢 低 | API 响应    | ✅ 已完成 |

---

## 五、复盘记录（2026-06-15）

### 已完成的修复

#### 1. Sku 模型修复
**文件：** `app/Models/Mall/Sku.php`

```php
// 添加 SoftDeletes trait
use Illuminate\Database\Eloquent\SoftDeletes;

class Sku extends Model
{
    use SoftDeletes;
    
    // 添加缺失的 casts
    protected $casts = [
        'origin_price' => 'decimal:2',
        'price' => 'decimal:2',
        'stock' => 'integer',
        'sale' => 'integer',
        'sort' => 'integer',
    ];
}
```

#### 2. OrderItem.sku() 关联修复
**文件：** `app/Models/Mall/OrderItem.php`

```php
public function sku(): BelongsTo
{
    return $this->belongsTo(Sku::class)
        ->withTrashed();  // 新增：支持软删除的 SKU
}
```

#### 3. order_items 表添加 cover 字段
**文件：** `database/migrations/0003_02_00_000001_create_orders_table.php`

```php
$table->string('cover')
    ->nullable()
    ->after('sku_name')
    ->comment('商品封面快照');
```

#### 4. OrderService bug 修复
**文件：** `app/Services/Mall/OrderService.php`

```php
// 修复前（错误）：
$item->sku->stocks -= $item->qty;  // Sku 表字段是 stock，不是 stocks
$item->sku->stocks += $item->qty;

// 修复后（正确）：
$item->sku->stock -= $item->qty;
$item->sku->stock += $item->qty;
```

#### 5. Product 访问器重命名
**文件：** `app/Models/Mall/Product.php`

| 旧名称 | 新名称 | 说明 |
|--------|--------|------|
| `stocks` | `total_stock` | 聚合字段，从所有 SKU 汇总 |
| `sales` | `total_sale` | 聚合字段，从所有 SKU 汇总 |

> 保留了旧访问器方法（标记为 `@deprecated`），确保向后兼容。

#### 6. Product $appends 配置
**文件：** `app/Models/Mall/Product.php`

```php
protected $appends = [
    'delivery_template',
    'price',
    'origin_price',
    'total_stock',
    'total_sale',
];
```

#### 7. Filament 后台更新
更新了以下文件中的字段引用：

| 文件 | 修改内容 |
|------|---------|
| `Backend/ProductsTable.php` | `stocks` → `total_stock`，添加价格 `¥` 前缀 |
| `Backend/ProductInfolist.php` | `stocks` → `total_stock`，`sales` → `total_sale`，添加 `¥` 前缀 |
| `Tenant/ProductsTable.php` | `stocks` → `total_stock` |
| `Tenant/ProductInfolist.php` | `stocks` → `total_stock`，`sales` → `total_sale` |

#### 8. API 资源更新
**文件：** `app/Http/Resources/Mall/ProductResource.php`

```php
// 字段重命名
'total_stock' => $this->total_stock,
'total_sale' => $this->total_sale,
```

---

### 修复统计

| 类别 | 数量 |
|------|------|
| 模型修复 | 2 个（Sku、Product） |
| 关联修复 | 1 个（OrderItem.sku） |
| Bug 修复 | 1 个（OrderService） |
| 迁移文件修改 | 2 个（orders、products） |
| Filament 组件更新 | 4 个 |
| API 资源更新 | 1 个 |

### 注意事项

1. **向后兼容**：Product 模型保留了旧访问器（`stocks`、`sales`），标记为 `@deprecated`
2. **API 变更**：ProductResource 返回的字段名已更改，前端需要同步更新
3. **数据库**：`order_items` 表新增了 `cover` 字段，需要运行迁移

---

## 六、待办事项

- [ ] 运行数据库迁移：`php artisan migrate`
- [ ] 更新前端代码，适配新的 API 字段名（`total_stock`、`total_sale`）
- [ ] 为现有订单数据补充 `cover` 快照（可选）

## 五、建议的修复顺序

1. **第一步**：修复 Sku 模型的 SoftDeletes（基础依赖）
2. **第二步**：修复 OrderItem 的 sku 关联（依赖第一步）
3. **第三步**：添加 order_items 的快照字段（可选，需要迁移）
4. **第四步**：完善 Sku 的 casts（代码优化）
