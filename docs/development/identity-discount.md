# 商品身份折扣功能设计

> 租户下的商品可针对特定用户身份设置百分比折扣（如 80 表示打 8 折）。用户在一个租户下只有一个身份，折扣在**结算预览、下单、购物车展示**时实时计算生效。

## 需求概述

- 租户可为商品按身份设置折扣，同一商品的不同 SKU 规格共享同一折扣
- 用户在一个租户下只有一个身份（`IdentityService::entry()` 变更身份时删除旧记录，前提成立）
- 折扣只影响**实体商品（Sku）**单价；身份本身（Identity）作为虚拟权益不参与折扣

## 现状核对（定价链路）

实施前必须理解现有计价口径，这是本方案的关键修正点：

| 链路 | 取价方式 | 位置 |
|------|----------|------|
| 加购快照 | `$sku->price` 写入 `price_at_add` | `CartService::addItem()` |
| 购物车小计/结算预览 | `price_at_add × qty`（`sub_total`） | `CartItem::getSubTotalAttribute()`、`CartController::preview()` |
| **下单定价** | `OrderItemDto` 构造时实时调 `getOrderablePrice()`，**不读 `price_at_add`** | `OrderItemDto::__construct()`、`CartController::createFromCart()` |

> ⚠️ **原方案缺陷**：原设计「加购时算折扣写入 `price_at_add`」在下单链路不生效——预览显示折扣价、订单按原价成交。**折扣必须进入 `OrderItemDto` 定价链路**（它是立即购买与购物车两条下单路径的唯一计价入口），`price_at_add` 仅作加购时的展示快照，不作为金额计算依据。

## 核心逻辑

1. **统一计价入口**：新增 `ProductDiscountService`，购物车列表、结算预览、下单、商品详情全部经它取价，保证各处金额一致
2. **实时计算**：折扣在读取时实时计算，不落购物车快照——身份变更/到期、折扣调整后自然生效，无快照过期问题
3. **折扣条件**：用户身份 + 商品必须属于同一租户，且身份在有效期内（`end_at` 为空或晚于当前）
4. **仅百分比折扣**：percent=80 表示原价 × 0.8，作用于 SKU 单价

## 数据库设计

### 商品折扣表（product_discounts）

按项目中间表惯例（参照 `pickup_point_product` / `topic_product` 模式），并入商品迁移文件 `0003_01_00_000001_create_products_table.php`，不新建增量迁移：

```php
Schema::create('product_discounts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->foreignId('identity_id')->constrained()->cascadeOnDelete();
    $table->unsignedTinyInteger('percent')->comment('折扣百分比(1-99, 80表示打8折)');
    $table->timestamps();

    $table->unique(['product_id', 'identity_id']);
    $table->check('percent between 1 and 99');
});
```

- **级联删除**：商品或身份删除时折扣自动清理，无需手动维护
- **percent 约束**：DB CHECK + 服务层/Filament 双重校验（1-99，禁止 0 折、原价、加价）
- 不存 `tenant_id` 冗余：租户隔离经由商品/身份外键天然保证，Filament 在 Product 资源下管理即自动隔离

### 模型

```php
#[Unguarded]
class ProductDiscount extends Model
{
    protected function casts(): array
    {
        return ['percent' => 'integer'];
    }

    /**
     * 关联商品
     *
     * @return BelongsTo<Product>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 关联身份
     *
     * @return BelongsTo<Identity>
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class);
    }
}
```

`Product` 模型补充关联（供 Filament 与查询使用）：

```php
/**
 * 关联身份折扣
 *
 * @return BelongsToMany<Identity>
 */
public function discounts(): BelongsToMany
{
    return $this->belongsToMany(Identity::class, 'product_discounts')
        ->withPivot(['percent'])
        ->withTimestamps();
}
```

## 定价服务设计（ProductDiscountService）

新增 `app/Services/Mall/ProductDiscountService.php`，统一折扣判定与取价：

```php
class ProductDiscountService implements ServiceInterface
{
    /**
     * 获取用户在指定租户下的有效身份 ID（一次查询）
     */
    public function identityIdFor(User $user, int $tenantId): ?int
    {
        return $user->identities()
            ->where('user_identity.tenant_id', $tenantId)
            ->where(function ($q) {
                $q->whereNull('user_identity.end_at')
                    ->orWhere('user_identity.end_at', '>', now());
            })
            // 防御性排序：entry() 保证单租户单身份，仍取最新一条兜底
            ->orderByDesc('user_identity.start_at')
            ->value('user_identity.identity_id');
    }

    /**
     * 获取用户对商品的折后 SKU 单价（无折扣时返回原价）
     */
    public function priceFor(User $user, Sku $sku): string
    {
        $percent = $this->percentFor($user, $sku->product);

        if ($percent === null) {
            return $sku->getOrderablePrice();
        }

        return $this->applyPercent($sku->getOrderablePrice(), $percent);
    }

    /**
     * 应用百分比折扣（bcmath，四舍五入保留 2 位）
     */
    public function applyPercent(string $price, int $percent): string
    {
        // price × percent / 100，先算 4 位再四舍五入到分
        $raw = bcdiv(bcmul($price, (string) $percent, 6), '100', 4);

        return number_format((float) $raw, 2, '.', '');
    }
}
```

`percentFor()` 内部按商品查 `product_discounts`，配合批量接口 `percentForProducts(User, Collection $products): array` 供购物车/结算按租户分组批量取折扣，避免 N+1。

## 接入点改造

| 接入点 | 改动 | 说明 |
|--------|------|------|
| `OrderItemDto` | `make()` 增加可选 `?string $price = null` 参数，传入时覆盖 `getOrderablePrice()` | **核心改动**，立即购买与购物车下单统一生效 |
| `CartController::createFromCart()` | 构造 DTO 前经 `ProductDiscountService::priceFor()` 取折后价传入 | 下单按折扣价成交，订单项快照天然记录折后价 |
| `OrderController`（立即购买） | 同上 | 不经购物车的路径同样生效 |
| `CartController::preview()` | 金额改用 `priceFor()` 实时计算（替代 `sub_total`） | 预览与下单金额口径一致 |
| `CartService::addItem()` | `price_at_add` 写入折后价 | 仅作展示快照，注释标明「金额以实时计算为准」 |
| `CartResource` / `CartItemResource` | 购物车列表展示实时折后价 + 划线原价 | `price_at_add` 不再作为展示依据 |
| `ProductResource` | 商品详情返回 `discount_price`（有身份折扣时） | 前端详情页展示会员价 |

> `CartItem::getSubTotalAttribute()` 基于 `price_at_add`，保留不动（向后兼容），但预览/下单金额一律走 `ProductDiscountService`，不引用 `sub_total`。

## 价格计算

```php
// percent=80 表示打 8 折：原价 × 80 ÷ 100，bcmath 计算，四舍五入保留 2 位
$discountedPrice = $service->applyPercent('99.90', 80); // '79.92'
```

- 全程 bcmath + string，禁用 float 直乘（原方案 `$originalPrice * 0.8` 有精度隐患）
- 计算基准为 **SKU 单价**（`Sku::price`），非商品价格
- 折后价写入订单项 `order_items.price` 快照，后续退款按成交价退，结算按成交金额，均无需改动

## 与优惠券的叠加规则

系统已有订单级优惠券抵扣（`CouponService::calculateDiscount()`，Fixed/Percent，记 `CouponOrder.discount_amount`）。**已确认规则：允许叠加，单价优先**：

1. 先按身份折扣计算 SKU 单价（本方案）
2. 订单金额 = Σ 折后单价 × 数量 + 运费
3. 优惠券在订单金额基础上抵扣

即身份折扣改「单价」，优惠券扣「总额」，互不冲突；优惠券按折后金额计算优惠基数。

## 查询逻辑（防 N+1）

购物车/结算场景商品跨租户，查询策略：

```php
// 1. 一次查询用户在各租户的有效身份（按租户分组）
// 2. 一次查询命中的 (product_id, identity_id) 折扣
// 3. 内存中匹配：商品 → 租户 → 身份 → 折扣 percent
$percentMap = $service->percentForProducts($user, $products);
```

禁止在 `foreach` 商品时逐条查询身份/折扣（原方案查询逻辑即为逐条模式）。

## Filament 管理界面

在 Tenant 面板 Product 资源下管理（租户隔离天然成立）：

- **入口**：Product 资源新增 `DiscountsRelationManager`（或 ProductForm 内 Repeatable），列出现有折扣
- **表单**：身份选择器（Select，限定当前租户且 `status` 启用）+ percent 数字输入（1-99，整数，前后端校验）
- **保存校验**：`identity.tenant_id === product.tenant_id`，跨租户错配拒绝（复合唯一键防重复，但不防错配，必须显式校验）
- Backend 面板只读查看

## 实施步骤

| 步骤 | 内容 | 文件 |
|------|------|------|
| 1 | 迁移：`product_discounts` 表并入商品迁移文件 | `database/migrations/0003_01_00_000001_create_products_table.php` |
| 2 | `ProductDiscount` 模型 + `Product::discounts()` 关联 | `app/Models/Mall/ProductDiscount.php`、`app/Models/Mall/Product.php` |
| 3 | `ProductDiscountService` 定价服务 | `app/Services/Mall/ProductDiscountService.php` |
| 4 | `OrderItemDto` 支持价格覆盖 | `app/Services/Mall/DTOs/OrderItemDto.php` |
| 5 | 下单链路接入（购物车 + 立即购买） | `CartController`、`OrderController` |
| 6 | 结算预览接入实时折扣价 | `CartController::preview()` |
| 7 | 购物车/商品详情 API 返回折扣价 | `CartResource`、`CartItemResource`、`ProductResource` |
| 8 | Filament 折扣管理（Tenant 管理 + Backend 只读） | `app/Filament/Tenant/Clusters/Mall/Resources/Products/` |
| 9 | 测试：折扣计算、叠加、过期身份、跨租户、预览与下单金额一致 | `tests/Feature/Mall/` |

## 关键设计决策

1. **折扣进入下单定价链路而非仅购物车快照** —— 修正原方案致命缺陷，`OrderItemDto` 是唯一计价入口，覆盖全部下单路径
2. **实时计算 + 展示快照分离** —— 金额一律实时算（身份变更/到期/折扣调整自然生效），`price_at_add` 降级为加购参考快照
3. **仅百分比折扣** —— percent 1-99 约束（DB CHECK + 验证双层）
4. **商品级折扣** —— 同一商品所有 SKU 共享折扣，计算基准为 SKU 单价
5. **允许与优惠券叠加** —— 身份折扣改单价、优惠券扣总额，顺序固定
6. **不冗余 tenant_id** —— 经商品/身份外键保证租户一致，保存时显式校验跨租户错配
7. **中间表并入商品迁移文件** —— 与 `pickup_point_product` 惯例一致

## 缺陷修正记录

| 原方案问题 | 严重度 | 修正 |
|------------|--------|------|
| 折扣只写 `price_at_add`，下单走 `getOrderablePrice()` 实时原价，折扣不生效 | 🔴 致命 | 折扣接入 `OrderItemDto` 定价链路 |
| 无快照刷新策略（身份到期/变更后价格过期） | 🟡 | 实时计算替代快照计价 |
| percent 无取值约束 | 🟡 | 1-99，CHECK + 验证 |
| float 乘法、无舍入规则 | 🟡 | bcmath + 四舍五入到分 |
| 未定义与优惠券叠加规则 | 🟡 | 单价优先、允许叠加 |
| 裸 SQL 表设计不合项目惯例 | 🟡 | Schema builder + 外键级联 + 并入商品迁移 |
| 无跨租户错配校验 | 🟡 | 保存时校验 identity 与 product 同租户 |
| 逐商品查询身份/折扣（N+1） | 🟡 | 批量查询接口 |
| 实施步骤缺立即购买、预览、API 展示、测试 | 🟡 | 步骤表补全 |

## 待确认问题

| 问题 | 默认方案 | 备注 |
|------|----------|------|
| 身份折扣是否需要在订单/后台标注「会员价」来源 | 暂不标注 | `order_items.price` 已是成交价，来源信息可后续在订单日志 context 中补充 |
| 商品详情页是否按身份展示划线原价 | 展示 | `ProductResource` 返回 `price` + `discount_price` |
| 折扣是否缓存（商品维度） | 一期不缓存 | 折扣表按商品查询压力可控，热点出现后再加缓存 |
