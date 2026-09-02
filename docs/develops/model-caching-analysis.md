# Eloquent 模型缓存分析

> 基于 `mike-bronner/laravel-model-caching` 包的模型缓存评估

## 包原理

- 在 Eloquent 模型上添加 `Cachable` trait，自动缓存查询结果（`get`、`first`、`find`、`paginate`、`pluck`、`count`、`sum` 等）
- Eager 加载的 `with()` 关系也会被缓存
- 模型创建/更新/删除时自动失效相关缓存（Redis 下使用 cache tag）
- 默认 `rememberForever`，无 TTL，依赖自动失效

## 关键配置

| 环境变量 | 默认值 | 说明 |
|---|---|---|
| `MODEL_CACHE_ENABLED` | `true` | 全局开关 |
| `MODEL_CACHE_STORE` | 默认 store | 专用缓存驱动 |
| `MODEL_CACHE_USE_DATABASE_KEYING` | `true` | 多数据库隔离 |
| `MODEL_CACHE_FALLBACK_TO_DB` | `false` | 缓存不可用时降级 |

## 注意事项

- Redis 为推荐缓存驱动（支持 cache tag）
- `select()` 自定义列查询不走缓存
- `inRandomOrder()` 自动跳过缓存
- 事务内更新不会自动清缓存，需手动 `flushCache()`
- 测试环境需关闭：`config(['laravel-model-caching.enabled' => false])`

---

## 项目模型总览

共 **69 个具体模型**，当前 **无任何模型使用 `Cachable` trait**。

| Domain | 模型数 |
|---|---|
| Mall | 28 |
| System | 13 |
| Finance | 12 |
| User | 11 |
| Campaign | 10 |
| Content | 9（含 2 个抽象类） |
| Foundation | 10 |
| BlockChain | 5 |

---

## 强烈推荐缓存

读多写少，查询开销大，风险最低。

### `Mall\Product`

- 价格/库存/销量通过 Sku 子查询聚合（`getTotalStockAttribute`、`getPriceAttribute`）
- `orderByMatch` 使用关联子查询排序
- 前台高频读取，商品编辑频率中等

### `Mall\ProductCategory`

- 分类树几乎不变，前台列表/筛选必用
- 全局 scope 按类型过滤

### `Mall\Brand`

- 品牌列表只读场景多
- 低写入频率

### `Mall\Banner`

- Banner 列表纯读
- 后台偶尔修改

### `Mall\Region`

- 行政区划数据，几乎不变
- 省市区三级联动高频读取

### `Mall\Express`

- 快递公司列表，配置型数据
- 极少增删改

### `Content\ContentCategory`

- 同 `ProductCategory`，分类树缓存
- 全局 scope 按类型过滤

### `Content\SinglePage`

- 单页内容，纯读场景

### `Content\Content`

- 文章列表高频读取
- 写入集中在发布时

### `BlockChain\Network`

- 网络配置读取，极少修改

### `BlockChain\ContractRepository`

- 合约仓库配置，配置型数据

---

## 推荐缓存

读多写少，有一定查询开销，需关注失效频率。

### `Mall\Sku`

- 库存查询高频（下单前校验）
- 库存变动也频繁 — 建议设置 `coolDown` 缓解缓存抖动

### `Mall\Delivery`

- 配送规则，配置型数据
- 低写入

### `Campaign\Coupon`

- `canBeUsed()` / `getUsageCountAttribute` 做 count 查询
- 活动期间读多，领券/用券会触发失效

### `Campaign\Lottery`

- `getAvailableDrawsForUser()` 多次 count 查询
- 抽奖操作会触发失效

### `Campaign\LotteryPrize`

- `hasUserReachedLimit()` count 查询

### `Campaign\Redpack`

- 活动配置，读多写少

### `User\UserRelation`

- 树遍历用 LIKE 查询（`getAncestors`、`getDescendants`、`getTeamStats`）
- 开销大，但重新绑定会触发失效

### `User\Identity`

- `checkOrderable()` 涉及多表查询
- 配置型数据，写入少

### `System\Tenant`

- `getModules()` 已有实例级缓存，加模型缓存可跨请求持久化

### `System\Administrator`

- `canAccessPanel()` 每次请求调 2 次 count
- 读极频繁，写入集中在权限变更

### `Foundation\Wechat` / `WechatMini` / `Alipay`

- 支付配置，极少修改

### `Finance\Plan`

- 套餐配置，读多写少

### `Content\ContentTag` / `Mall\ProductTag`

- 标签列表，配置型数据

---

## 不建议缓存

| 模型 | 原因 |
|---|---|
| `Mall\Order` / `OrderItem` / `OrderShipping` | 写极频繁（状态流转），缓存频繁失效无收益 |
| `Mall\Refund` / `RefundItem` / `RefundLog` | 退款流程频繁写 |
| `Mall\Cart` / `CartItem` | 用户购物车，每个请求可能变 |
| `Finance\PaymentOrder` / `PaymentRefund` | 支付流程高频写 |
| `Finance\Voucher` / `VoucherLog` | 财务流水，写密集 |
| `Finance\Invoice*` | 发票申请流程写密集 |
| `User\Address` | 用户地址，读写均衡 |
| `User\UserProfile` | 用户资料，读写均衡 |
| `User\LoginRecord` / `User\SmsCode` | 高频写入，Prunable |
| `System\ApiLog` / `System\DBLog` / `System\FailedJob` | 日志类，纯写入 |
| 所有 Pivot 模型 | 多对多关联表，读写混合 |

---

## 实施建议

### 1. 安装

```bash
composer require mike-bronner/laravel-model-caching
```

### 2. 按需逐个模型启用（不在 BaseModel 启用）

逐个评估，在需要缓存的模型上单独添加 trait：

```php
// app/Models/Mall/Product.php
use GeneaLabs\LaravelModelCaching\Traits\Cachable;

class Product extends Model
{
    use Cachable;

    // ...
}
```

### 3. 首批缓存模型（5 个）

| 模型 | 理由 |
|---|---|
| `Product` | 最大收益点，查询开销最大 |
| `ProductCategory` | 分类树几乎不变 |
| `Brand` | 品牌列表纯读 |
| `Banner` | Banner 列表纯读 |
| `Region` | 行政区划数据不变 |

### 4. 测试环境关闭

```php
// tests/TestCase.php
protected function setUp(): void
{
    parent::setUp();

    config(['laravel-model-caching.enabled' => false]);
}
```

### 5. PHPStan 注解

在使用 `Cachable` 的模型上添加 `@mixin \GeneaLabs\LaravelModelCaching\CachedBuilder` 避免误报。

### 6. 库存类模型设置 coolDown

```php
class Sku extends Model
{
    use Cachable;

    protected $cacheCooldownSeconds = 60; // 60 秒内不重复失效
}
```

---

## 关联查询缓存问题与处理

使用 `Cachable` 后，关联查询（Eager Load）有时会出现查询不到数据或数据过期的问题。以下是常见原因和解决方案。

### 常见问题

#### 1. 关联模型更新后，父模型缓存未失效

```
Product 更新 → 只失效 Product 的缓存
             → 但 ProductCategory/Brand 的列表缓存仍包含旧数据
```

**原因**：包只自动失效被操作模型自身的缓存，不追踪业务层面的关联依赖。

#### 2. Pivot 表变更不触发失效

多对多关系（如 `Coupon ↔ Product`）更新 pivot 时，包无法自动检测到 pivot 变更，导致缓存未失效。

#### 3. Eager Load 缓存与实际数据不一致

`with()` 预加载的结果被整体缓存，但关联数据在缓存写入后被其他请求更新，导致脏数据。

#### 4. 复杂子查询绕过缓存

`whereHas`、`whereExists` 等转成的子查询有时不被缓存识别，导致查询不走缓存或缓存命中失败。

### 解决方案

#### 方案一：双向缓存（推荐）

关联的双方都加 `Cachable`，让任一方更新时都能触发对方缓存失效：

```php
class Product extends Model
{
    use Cachable;
}

class ProductCategory extends Model
{
    use Cachable;
}

class Brand extends Model
{
    use Cachable;
}
```

#### 方案二：写操作后主动清关联缓存

在 Service 层更新数据后，手动清除相关模型缓存：

```php
// ProductService.php
public function updateProduct(Product $product, array $data): Product
{
    $product->update($data);

    // 清除自身 + 相关模型缓存
    Product::flushCache();
    ProductCategory::flushCache();
    Brand::flushCache();

    return $product;
}
```

#### 方案三：关系变更时清除双向缓存

多对多关系同步后，清除两侧缓存：

```php
class Coupon extends Model
{
    use Cachable;

    public function syncProducts(array $ids): void
    {
        $this->products()->sync($ids);

        // 清除产品侧缓存
        Product::flushCache();
    }
}
```

#### 方案四：Observer 集中管理

用 Observer 统一处理关联缓存清除，避免遗漏：

```php
// app/Observers/ProductObserver.php
class ProductObserver
{
    public function updated(Product $product): void
    {
        ProductCategory::flushCache();
        Brand::flushCache();
    }

    public function deleted(Product $product): void
    {
        ProductCategory::flushCache();
        Brand::flushCache();
    }
}
```

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    Product::observe(ProductObserver::class);
}
```

#### 方案五：复杂查询手动缓存

对 `whereHas` 等复杂子查询，不依赖包的自动缓存，手动使用 `Cache::remember()`：

```php
use Illuminate\Support\Facades\Cache;

class ProductScope
{
    public static function cachedWithMinSkuStock(int $minStock): Collection
    {
        $key = "products_with_stock_{$minStock}";

        return Cache::remember($key, 300, function () use ($minStock) {
            return Product::query()
                ->with('skus')
                ->whereHas('skus', fn ($q) => $q->where('stock', '>=', $minStock))
                ->get();
        });
    }
}
```

#### 方案六：Pivot 操作后手动清缓存

多对多关系同步/_detach/attach 后手动清除：

```php
// 同步产品标签
$product->tags()->sync($tagIds);
Product::flushCache();
ProductTag::flushCache();

// 解绑优惠券产品
$coupon->products()->detach($productId);
Coupon::flushCache();
Product::flushCache();
```

### 处理模式速查

| 场景 | 方案 |
|------|------|
| 单表简单查询 | 直接用 `Cachable`，自动失效 |
| Eager load 关联 | 双方都加 `Cachable` |
| Pivot 表操作 | 手动 `flushCache()` |
| 复杂子查询 | 手动 `Cache::remember()` |
| 高频写模型 | 设 `cacheCooldownSeconds` |
| 需要精确控制 | Observer + 手动清缓存 |

### 核心原则

**谁的数据变了，就把依赖它的模型缓存都清掉。** 包的自动失效只覆盖直接操作的模型，不覆盖业务层面的关联依赖。
