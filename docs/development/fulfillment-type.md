# 商品履约方式（FulfillmentType）方案

> 为可下单商品（Orderable）引入履约方式概念，区分「快递邮寄 / 门店自提 / 虚拟商品」三种交付链路。商品可同时支持多种履约方式，下单时选择履约方式，订单内商品须支持所选方式。按一期、二期分阶段落地。

## 背景

当前订单系统通过 `Orderable` 契约统一处理可下单主体（商品规格 `Sku`、身份订阅 `Identity` 等）的校验、定价、库存扣减与回退。但交付链路是硬编码的：

- `Sku`（实体商品）走完整物流链路：运费模板计算 → 发货 → 签收；
- `Identity`（虚拟权益）无库存、无物流，靠 `needsReturn()` 返回 `false` 区分退款是否退货。

业务需要更显式的履约方式定义：**一个商品可同时支持多种履约方式**（如既支持快递邮寄、也支持门店自提），下单时由用户选择一种履约方式，订单内所有商品必须支持所选方式。同时为后续新增可下单模型（充值套餐、预约项目等）提供统一扩展点。

## 类型定义

新增枚举 `App\Enums\Mall\FulfillmentType`：

| 枚举值 | 标签 | 运费 | 物流发货 | 库存扣减 | 订单链路 |
|--------|------|------|----------|----------|----------|
| `mail` | 快递邮寄 | 按运费模板 | 需要（order_shipping） | 正常扣减 | 现有完整链路 |
| `pickup` | 门店自提 | 免运费 | 不需要 | 正常扣减 | 待付款 → 待自提 → 核销 |
| `virtual` | 虚拟商品 | 免运费 | 不需要 | 可不扣（no-op） | 待付款 → 完成 |

> 说明：一期不引入「同城配送」。后续需要时只需在枚举中补充 case 并增加对应运费/物流分支，不影响本方案结构。

## 可下单主体（Orderable）现状

| 模型 | 说明 | 履约方式归属 |
|------|------|--------------|
| `Mall\Sku` | 商品规格（实体） | 委托商品：`in_array($type, $product->fulfillment_type)`，**仅支持 `mail` / `pickup`** |
| `User\Identity` | 身份订阅（虚拟权益） | 仅支持 `virtual` |

---

## 核心设计

### 数据模型

| 层级 | 字段 | 取值 | 说明 |
|------|------|------|------|
| 商品 | `products.fulfillment_type` | jsonb 数组，如 `["mail", "pickup"]` | 该商品支持的全部履约方式，可多选；**商品仅可选 `mail` / `pickup`，`virtual` 仅用于身份等虚拟权益** |
| 订单 | `orders.fulfillment_type` | 单值（`mail` / `pickup` / `virtual`） | 下单时用户所选的一种方式，落库快照 |

### 下单流程

1. 用户从商品支持的履约方式集合中选择一种（前端按商品支持集合渲染可选项）；
2. 下单接口接收所选履约方式，遍历订单项校验 `supportsFulfillmentType()`，**任一商品不支持则拒绝整单**；
3. 校验通过后创建订单，`orders.fulfillment_type` 落所选值；
4. 运费计算：订单级类型为 `mail` 才按运费模板计费，`pickup` / `virtual` 免运费。

### 混合订单处理（已确认：方案 A）

购物车同时含「仅支持邮寄的商品」与「仅支持虚拟的商品」时，下单只能选一种方式，任一方式都无法同时满足两类商品。**已确认采用方案 A：禁止混合，整单选一种。**

| 已确认项 | 结论 |
|----------|------|
| 混合订单策略 | **A. 禁止混合**：订单内所有商品必须都支持所选方式，不满足则拒绝下单 |
| 下单接口 | `createOrders` / `createOrder` **新增 `FulfillmentType` 参数**，由 CartController / OrderController / IdentityService 传入所选方式 |
| 校验失败策略 | **整单拒绝**：任一订单项不支持所选方式即抛异常，不做剔除或部分兼容 |
| 迁移方式 | **直接修改源迁移文件**（products / orders），不新建增量迁移 |
| 前端渲染 | **一期纳入**：商品详情 / 购物车 API 返回 `fulfillment_type` 支持集合，供前端渲染可选履约方式 |
| 订单展示 | **一期纳入**：后台订单列表/详情、前端订单 API 展示/返回履约方式 |
| Identity 下单 | **固定 `FulfillmentType::Virtual`**：IdentityService 下单直接传入，前端不可选 |

> 若未来需要支持邮寄+虚拟同购，再评估方案 B（按履约方式拆单），不影响一期结构。

---

## 一期：类型基础落地

一期目标：将履约方式定义为契约级能力，完成多值字段、下单选择与校验、免运费短路与表单联动，保证下单链路正确性。订单状态机改动留二期。

### 1. 枚举

创建 `app/Enums/Mall/FulfillmentType.php`，包含 `getLabel()` 与 `getColor()`（参照 `ProductStatus` 风格）。

### 2. `Orderable` 契约新增方法

在 `app/Contracts/Orderable.php` 增加：

```php
/**
 * 是否支持指定履约方式（决定交付链路与运费计算）
 *
 * 可订购主体可同时支持多种履约方式（如商品可邮寄也可自提），
 * 下单时校验所选履约方式是否被所有订单项支持。
 */
public function supportsFulfillmentType(FulfillmentType $type): bool;
```

所有可下单主体统一声明履约能力，服务层只面向契约编程。

### 3. `Sku` 实现

委托商品判断（商品可多选履约方式）：

```php
public function supportsFulfillmentType(FulfillmentType $type): bool
{
    return in_array($type, $this->product->fulfillment_type ?? [], true);
}
```

### 4. `Identity` 实现

虚拟权益仅支持 `virtual`：

```php
public function supportsFulfillmentType(FulfillmentType $type): bool
{
    return $type === FulfillmentType::Virtual;
}
```

### 5. 数据库迁移（直接修改源迁移文件）

`products` 表字段（多值）：

```php
$table->jsonb('fulfillment_type')
    ->default(json_encode([FulfillmentType::Mail->value]))
    ->comment('履约方式(多选): mail=快递邮寄, pickup=门店自提, virtual=虚拟商品');
```

`orders` 表字段（单值 = 下单所选方式）：

```php
$table->string('fulfillment_type', 16)
    ->nullable()
    ->index()
    ->comment('订单履约方式: mail=快递邮寄, pickup=门店自提, virtual=虚拟商品');
```

存量商品默认 `["mail"]`，无回填风险。一期不建自提点表（自提地址/门店信息留二期）。

### 6. `Product` / `Order` 模型

- `Product`：`casts()` 增加 `'fulfillment_type' => 'array'`，补语义化辅助方法：
  - `needsShipping(): bool` —— 是否支持快递邮寄；
  - `supportsFulfillmentType(FulfillmentType $type): bool` —— 是否支持指定方式；
  - `isFreeFreight(): bool` —— 是否免运费（不支持邮寄时）。
- `Order`：`casts()` 增加 `'fulfillment_type' => FulfillmentType::class`（单值 = 所选方式）。

### 7. 商品表单联动（`ProductForm`）

- 新增履约方式 **CheckboxList 多选**（默认 `["mail"]`），**选项仅 `mail` / `pickup`，不提供 `virtual`**（虚拟商品仅用于身份等虚拟权益）；
- 勾选含 `mail` 时显示「运费模板」「退货地址」，否则隐藏；
- 仅当未勾选 `virtual` 时显示「库存扣减方式」；
- SKU 表单不动。

### 8. `OrderService` 下单：选择与校验

- `createOrders` / `createOrder` 增加 `FulfillmentType $fulfillmentType` 参数（下单所选方式）；
- 下单前遍历订单项，校验 `$item->orderable->supportsFulfillmentType($fulfillmentType)`，任一不支持则抛异常拒绝下单；
- 订单创建时 `orders.fulfillment_type` 落所选值；
- 调用方（CartController / OrderController / IdentityService）需传入选中的履约方式。

### 9. `DeliveryService` 运费计算

- 运费计算接收所选履约方式（或直接判断订单级类型）；
- 订单级类型为 `mail` 时才按运费模板计算，否则返回 `'0.00'`；
- 保留 `calculateOrderTotals` 等既有逻辑不变。

### 一期验收

- 枚举、契约、模型改动通过 Pint 格式化与静态检查；
- 下单校验测试：所选方式被所有商品支持 → 成功；任一商品不支持 → 拒绝；
- 运费计算测试覆盖：全自提、全虚拟、混合（含 mail）三种场景；
- 表单多选与联动在 Backend / Tenant 双面板生效。

---

## 二期：履约链路深化

二期目标：让订单状态机、退款流程与核销体系按履约方式差异化流转。涉及跨模块改动，独立评审。

### 1. 订单状态机分支（`OrderStatus`）

| 履约方式 | 状态链路 | 说明 |
|----------|----------|------|
| `mail` | 现有链路不变 | `Paid → Preparing → Delivered → Signed → Completed` |
| `pickup` | 新增「待自提」「已核销」状态 | 付款后生成核销码，到店核销后完成 |
| `virtual` | `Paid → Completed` 直连 | 跳过备货/发货/签收，支付成功即完成 |

需要在 `OrderStatus` 中新增 `PickupPending`、`Verified` 等状态，并调整 `HasStateMachine` 的前驱/后继映射。

### 2. 自提核销体系

- 新增自提点/门店数据（可放 `store_configures` 或独立表）；
- 订单生成核销码（二维码/字符串），支持商家端核销；
- 订单项与自提点关联。

### 3. 退款物流区分（`Refundable`）

契约注释已预留「实体商品 vs 虚拟权益」区分：

- `mail` / `pickup`：`needsReturn()` 返回 `true`，走退货流程；
- `virtual`：`needsReturn()` 返回 `false`，直接退款（`Identity` 已实现为撤销身份）。

### 4. 下单校验强化

- 一期下单时校验所选方式被商品支持，二期可进一步：如 `pickup`/`virtual` 不允许填写收货地址、下单前端按商品支持集合渲染可选履约方式等。

---

## 涉及文件清单

| 阶段 | 文件 | 改动 |
|------|------|------|
| 一期 | `app/Enums/Mall/FulfillmentType.php` | 新增枚举 |
| 一期 | `app/Contracts/Orderable.php` | 新增 `supportsFulfillmentType()` |
| 一期 | `app/Models/Mall/Sku.php` | 实现契约方法 |
| 一期 | `app/Models/User/Identity.php` | 实现契约方法 |
| 一期 | `database/migrations/0003_01_00_000001_create_products_table.php` | products 加 jsonb 多值字段 |
| 一期 | `database/migrations/0003_02_00_000001_create_orders_table.php` | orders 加单值字段 |
| 一期 | `app/Models/Mall/Product.php` | casts + 辅助方法 |
| 一期 | `app/Models/Mall/Order.php` | casts |
| 一期 | `app/Filament/Tenant/Clusters/Mall/Resources/Products/Schemas/ProductForm.php` | 多选 + 联动 |
| 一期 | `app/Services/Mall/OrderService.php` | 下单参数 + 校验 + 落所选值 |
| 一期 | `app/Services/Mall/DeliveryService.php` | 按所选方式判断运费 |
| 一期 | `tests/` | 下单校验 + 运费计算测试 |
| 二期 | `app/Enums/Mall/OrderStatus.php` | 状态机分支 |
| 二期 | `app/Models/Mall/OrderShipping.php` 等 | 核销码、自提点 |
| 二期 | `app/Contracts/Refundable.php` 实现 | 退款物流区分 |
