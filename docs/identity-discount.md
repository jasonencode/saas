# 商品身份折扣功能设计

## 需求概述

租户下的商品可以针对特定用户身份设置百分比折扣。用户在一个租户下只有一个身份，折扣在加入购物车时即时生效。

## 核心逻辑

1. 用户在一个租户下只有一个身份
2. 折扣计算：用户身份 + 商品必须属于同一租户
3. 折扣类型：仅支持百分比折扣（如 80 表示打8折）
4. 加入购物车时计算折扣价，直接写入 `price_at_add`

## 数据库设计

### 商品折扣表

```sql
CREATE TABLE product_discounts (
    product_id BIGINT UNSIGNED NOT NULL COMMENT '商品ID',
    identity_id BIGINT UNSIGNED NOT NULL COMMENT '身份ID',
    percent SMALLINT UNSIGNED NOT NULL COMMENT '折扣百分比 (80表示打8折)',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (product_id, identity_id)
) COMMENT '商品折扣表';
```

## 查询逻辑

```php
// 1. 获取用户在该商品所属租户下的身份
$identityId = $user->identities()
    ->where('user_identity.tenant_id', $product->tenant_id)
    ->where(function ($q) {
        $q->whereNull('user_identity.end_at')
          ->orWhere('user_identity.end_at', '>', now());
    })
    ->value('user_identity.identity_id');

// 2. 查询该身份对这个商品的折扣
$discount = ProductDiscount::where('product_id', $product->id)
    ->where('identity_id', $identityId)
    ->first();
```

## 价格计算

```php
// percent=80 表示打8折，即原价 × 0.8
$discountedPrice = $originalPrice * ($discount->percent / 100);
```

## 实施步骤

| 步骤 | 内容 | 文件 |
|------|------|------|
| 1 | 创建 migration: `product_discounts` 表 | `database/migrations/` |
| 2 | 创建 `ProductDiscount` 模型 | `app/Models/Mall/ProductDiscount.php` |
| 3 | 修改 `Product` 模型添加 `discounts()` 关联 | `app/Models/Mall/Product.php` |
| 4 | 修改 `Product` 模型添加 `getDiscountedPrice()` 方法 | `app/Models/Mall/Product.php` |
| 5 | 创建 Filament 后台管理界面 | `app/Filament/` |

## 关键设计决策

1. **仅百分比折扣** - 简化逻辑，如 percent=80 表示打8折
2. **商品级别折扣** - 同一商品的所有 SKU 规格共享同一折扣
3. **复合主键** - `(product_id, identity_id)` 作为主键，无需单独 `id`
4. **无冗余字段** - 不存储 `tenant_id`，通过商品或身份关联获取
