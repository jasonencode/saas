# 商城模块（Mall Module）完整代码分析报告

> **分析时间**: 2025-07-16
> **复查时间**: 2026-06-15（复查结果：所有列出问题均未修复）
> **分析范围**:
> - `app/Models/Mall` (25 个模型)
> - `app/Http/Controllers/Mall` (5 个控制器)
> - `app/Services/Mall` (5 个服务类)
> - `app/Enums/Mall` (7 个枚举)
> - `app/Events/Mall` (10 个事件)
> - `app/Filament/Tenant/Clusters/Mall` (Filament 后台资源)
> - `app/Http/Resources/Mall` (11 个 API 资源)
> - `routes/apis/mall.php` (路由定义)
> - `database/migrations/` (相关迁移文件)
> - `tests/Feature/Mall/`, `tests/Unit/Mall/` (测试)
>
> **分析人**: AtomCode (deepseek-v4-flash)  
> **项目**: Saas.Foundation

---

## 1. 整体架构概览

### 1.1 模块架构图

```
┌─────────────────────────────────────────────────────────────────┐
│                        API 层 (Controllers)                      │
│   IndexController  ProductController  CategoryController        │
│   CartController   OrderController                              │
├─────────────────────────────────────────────────────────────────┤
│                       服务层 (Services)                          │
│   OrderService  CartService  DeliveryService                    │
│   ProductService  StoreService                                  │
├─────────────────────────────────────────────────────────────────┤
│                       领域层 (Models)                            │
│   Product  Sku  Order  OrderItem  Cart  CartItem  Refund ...    │
│   + Events, Enums, DTOs                                         │
├─────────────────────────────────────────────────────────────────┤
│                     展示层 (Filament)                            │
│   ProductsResource  OrdersResource  RefundsResource ...         │
├─────────────────────────────────────────────────────────────────┤
│                     基础设施 (Migrations)                        │
│   4 个基线迁移 + 1 个全文搜索补充迁移                            │
└─────────────────────────────────────────────────────────────────┘
```

### 1.2 各层统计

| 层级                     | 组件                                                                                                                                                                                                                                                                            | 数量     |
|------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|--------|
| **Models**             | Product, Sku, Order, OrderItem, Cart, CartItem, Refund, RefundItem, Banner, Brand, Delivery, DeliveryRule, Express, OrderAddress, OrderLog, OrderShipping, ProductCategory, ProductLog, RefundExpress, RefundLog, Region, ReturnAddress, StoreApply, StoreConfigure, Supplier | **25** |
| **Enums**              | OrderStatus, ProductStatus, RefundStatus, DeliveryType, DeductStockType, ApplyStatus, RegionLevel                                                                                                                                                                             | **7**  |
| **Events**             | OrderCreated, OrderPaid, OrderCanceled, OrderDelivered, OrderSigned, OrderCompleted, OrderPreparing, OrderPartiallyShipped, RefundInitialized + 2 基类                                                                                                                          | **10** |
| **Controllers**        | IndexController, ProductController, CategoryController, CartController, OrderController                                                                                                                                                                                       | **5**  |
| **Services**           | OrderService, CartService, DeliveryService, ProductService, StoreService                                                                                                                                                                                                      | **5**  |
| **API Resources**      | ProductResource, ProductCollection, CartResource, CartItemResource, CheckoutResource, OrderResource, OrderItemResource, OrderAddressResource, BrandResource, StoreConfigureResource, OrderCollection                                                                          | **11** |
| **Filament Resources** | Banners, Brands, Categories, Deliveries, Orders, Products, Refunds, ReturnAddresses, Suppliers                                                                                                                                                                                | **9**  |
| **Migrations**         | `0003_00_00_000001_create_mall_bases_table`<br>`0003_01_00_000001_create_products_table`<br>`0003_01_01_000000_create_carts_table`<br>`0003_02_00_000001_create_orders_table`<br>`0003_01_00_000002_add_fulltext_search_to_products_table`                                    | **5**  |

---

## 2. 模型关系图

```
                    ProductCategory (1) ──→ (∞) Product (1) ──→ (∞) Sku
                                             Product (1) ──→ (∞) ProductLog
                                             Product (∞) ──→ (1) Brand
                                             Product (∞) ──→ (1) Delivery

                    Cart (1) ──→ (∞) CartItem
                    CartItem (∞) ──→ (1) Sku

                    Order (1) ──→ (∞) OrderItem
                    Order (1) ──→ (∞) OrderLog
                    Order (1) ──→ (∞) OrderShipping
                    Order (1) ──→ (1) OrderAddress
                    Order (1) ──→ (∞) Refund

                    Refund (1) ──→ (∞) RefundItem
                    Refund (1) ──→ (1) RefundExpress

                    Delivery (1) ──→ (∞) DeliveryRule
                    Supplier (∞) ──→ (1) Tenant
                    StoreConfigure (1) ──→ (1) Tenant
```

---

### 3.3 🟢 低级别 / 建议优化

> 以下问题均已复查（2026-06-15），全部 **❌ 未修复**。

| #       | 文件                                                          | 状态 | 问题描述                                                                                                                 |
|---------|-------------------------------------------------------------|----|----------------------------------------------------------------------------------------------------------------------|
| **L1**  | `app/Models/Mall/Product.php:41-42`                         | ✅  | Product 有 `weight` 和 `volume` 的 `decimal:2` cast，但 products 表迁移中**没有**这两个列。如需重量/体积应放在 SKU 层级，否则应移除死代码                |
| **L2**  | `app/Services/Mall/OrderService.php:449`                    | ✅  | ~~`deleteExpress()` 缺少状态校验~~ 已添加前置校验，仅允许 `Delivered`/`PartiallyShipped` 状态下删除物流记录（2026-06-15）                        |
| **L3**  | `app/Services/Mall/OrderService.php:262-285`                | —  | `cancel()` 只在 `DeductStockType::Ordered` 时回退库存。如果商品是"付款减库存"（Paid），取消时不会回退库存——但这可能是有意设计（Paid 场景下未付款则无需回退）             |
| **L4**  | `app/Models/Mall/Refund.php:76-78`                          | ❌  | `refunded()` 方法是空实现，退款成功后的回调逻辑未实现                                                                                    |
| **L5**  | `app/Http/Resources/Mall/OrderResource.php`                 | ❌  | 多处直接使用 `$this->resource->user?->username`，如果未加载 `user` 关系且 user_id 有效，会产生 N+1 查询。应使用 `whenLoaded()`                  |
| **L6**  | `app/Http/Controllers/Mall/IndexController.php`             | ❌  | `brands()` 和 `banners()` 方法返回空数据 `ApiResponse::success()`，未实现实际查询逻辑                                                  |
| **L7**  | `routes/apis/mall.php:24-30`                                | —  | 使用 `whereNumber('category')` / `whereNumber('product')` 限制了路由参数为纯数字。Product 通过 ID 查找是 OK 的，但如果未来改用 slug 或 UUID 则需要修改 |
| **L8**  | `app/Console/Commands/Mall/OrderAutoCompleteCommand.php:54` | ❌  | `chunk(100)` 循环内每次调用 `$service->complete($order)` dispatch 事件，大量超时订单时可能存在性能问题。建议批量更新+单次事件                            |
| **L9**  | `app/Models/Mall/Supplier.php`                              | —  | Supplier 模型存在但**没有任何 API 路由或控制器**，仅用于 Filament 后台管理                                                                  |
| **L10** | `app/Services/Mall/OrderService.php:150`                    | ❌  | `$order->tenant->notify(...)` 可能触发 N+1（如果 tenant 未预加载），建议 `$order->load('tenant')` 或在事务外 notify                      |

---

## 4. 架构设计亮点

### ✅ 按租户分单逻辑

```php
// OrderService::createOrders()
$orders = $itemsCollect->groupBy('tenantId')
    ->map(function ($group, $tenantId) use ($user, $addr, $remark) {
        return $this->createTenantOrder((int) $tenantId, ...);
    });
```

通过 `tenantId` 自动拆分订单，完美适配多租户 SaaS 架构。

### ✅ 集中式订单状态机

```php
// OrderService::assertCan()
$ok = match ($transition) {
    OrderStatus::Canceled, OrderStatus::Paid => $current === OrderStatus::Pending,
    OrderStatus::Preparing => $current === OrderStatus::Paid,
    // ...
};
```

清晰定义了各状态转换规则，避免状态混乱。

### ✅ 事件驱动架构

每个订单状态变更都会 dispatch 对应事件（`OrderCreated`, `OrderPaid`, `OrderDelivered` 等），方便解耦扩展日志、通知、结算等模块。

### ✅ 灵活的运费模板系统

- 支持按数量/重量/体积三种计费模式
- 支持省市区三级配送规则匹配（精确匹配优先）
- 支持包邮门槛
- 有完善的单元测试覆盖（`tests/Feature/Mall/DeliveryServiceTest.php` — 30 个 Test 方法）

### ✅ DTO 领域对象

```php
// OrderItemDto
class OrderItemDto implements Arrayable
{
    public function __construct(public Sku $sku, public int $qty = 1, ...)
    {
        // 构造时自动校验商品状态和库存
    }
}
```

下单时在 DTO 构造阶段完成商品校验，职责清晰。

### ✅ 现代化 PHP 8 属性

广泛使用：

- `#[Unguarded]` — 替代传统的 `$guarded = []`
- `#[UsePolicy(...)]` — 声明式权限策略
- `#[Scope]` — 声明式查询作用域
- `#[WithoutTimestamps]` — 控制时间戳
- `#[Table]` — 自定义表配置

---

## 5. 数据库设计评估

### 5.1 索引覆盖

| 表             | 索引完整性   | 备注                                                                                        |
|---------------|---------|-------------------------------------------------------------------------------------------|
| `products`    | ✅ 8 个索引 | tenant, category_id, brand_id, delivery_id, status, created_at, deleted_at, (status+sort) |
| `skus`        | ✅ 3 个索引 | product_id, code, deleted_at                                                              |
| `orders`      | ✅ 7 个索引 | no(unique), tenant, user, status, expired_at, paid_at, created_at                         |
| `order_items` | ✅ 3 个索引 | order_id, product_id, sku_id, order_shipping_id                                           |
| `carts`       | ✅ 4 个索引 | 2 个 unique 复合索引 + deleted_at                                                              |
| `cart_items`  | ✅ 3 个索引 | cart_id(unique+sku_id), product_id, sku_id, tenant                                        |
| `refunds`     | ✅ 综合索引  | 包含常用查询字段                                                                                  |

### 5.2 外键约束

| 表             | 外键                                               | 状态       |
|---------------|--------------------------------------------------|----------|
| `cart_items`  | `cart_id REFERENCES carts(id) ON DELETE CASCADE` | ✅ 有 FK   |
| `products`    | category_id, brand_id, delivery_id               | ❌ 未定义 FK |
| `skus`        | product_id                                       | ❌ 未定义 FK |
| `orders`      | tenant_id, user_id                               | ❌ 未定义 FK |
| `order_items` | order_id, product_id, sku_id                     | ❌ 未定义 FK |

> **注**: 该应用使用应用层代码保证数据完整性（Laravel 的 BelongsTo 关系 + Policy），但在高并发场景下，外键约束能提供额外保护。

---

## 6. 测试覆盖情况

| 测试文件                                            | 测试内容                       | 方法数 | 覆盖度    |
|-------------------------------------------------|----------------------------|-----|--------|
| `tests/Feature/Mall/MallApiTest.php`            | API 端点的集成测试（商品列表、详情、购物车认证） | 12  | ✅ 基础覆盖 |
| `tests/Feature/Mall/DeliveryServiceTest.php`    | 运费计算的完整测试（按数量/重量/规则匹配/包邮等） | 30  | ✅ 高覆盖  |
| `tests/Feature/Mall/OrderExpirationTest.php`    | 订单过期逻辑                     | —   | ✅      |
| `tests/Feature/TenantMallConfigurePageTest.php` | Filament 店铺配置页面            | —   | ✅      |
| `tests/Unit/Dtos/OrderItemDtoTest.php`          | 订单 DTO 单元测试                | —   | ✅      |
| `tests/Unit/Mall/OrderAmountTest.php`           | 订单金额计算                     | —   | ✅      |
| `tests/Unit/Enums/Mall/`                        | 枚举的 label/color 测试         | —   | ✅      |

**主要缺口**:

- CartService / OrderService 缺少单元测试
- Filament 资源缺少页面测试
- 退款流程缺少测试

---

## 7. 修复计划（按优先级）

> **2026-06-15 复查**: 以下所有修复项均 **❌ 未修复**。

### Phase 1 — 必须修复

| # | BUG                              | 影响范围        | 状态 |
|---|----------------------------------|-------------|----|
| 1 | M6 — `CartController::preview()` | 结算预览功能运行时错误 | ❌  |

### Phase 2 — 推荐修复

| # | 问题                                  | 影响范围                      | 状态     |
|---|-------------------------------------|---------------------------|--------|
| 2 | M1 — `Product::storeConfigure()` 关系 | ~~可能加载错误记录~~ 非问题（每租户单条记录） | ✅ 无需修复 |
| 3 | L2 — `deleteExpress()` 缺少校验         | 可能导致非法状态转换                | ✅ 已修复 |

### Phase 3 — 优化建议

| # | 问题                            | 说明       | 状态 |
|---|-------------------------------|----------|----|
| 4 | M4 — Product 价格懒加载 N+1        | 性能优化     | ❌  |
| 5 | L6 — IndexController 空实现      | 功能完整性    | ✅  |
| 6 | L8 — 自动完成命令 chunk             | 大订单量场景性能 | ❌  |
| 7 | 数据库外键约束                       | 数据完整性加固  | ❌  |
| 8 | OrderService 单元测试             | 测试覆盖面    | ❌  |
| 9 | RefundService 实现 `refunded()` | 退款回调逻辑   | ❌  |

---

## 8. 总结

商城模块整体架构设计清晰，**按租户分单 + 事件驱动 + 状态机**的组合是亮点。代码风格现代（PHP 8 属性广泛应用），Traits 复用率高。

主要问题集中在**枚举比较习惯**（`$status` 当 bool 用）和**类型声明不一致**上，属于常见的从传统 PHP 迁移到枚举/强类型的"惯性 BUG"。运费计算系统的测试覆盖是其质量最好的部分。

**如果切换到构建模式，建议从 Phase 1 开始修复。**
