# 商品履约方式（FulfillmentType）方案

> 为可下单商品（Orderable）引入履约方式概念，区分「快递邮寄 / 门店自提 / 虚拟商品」三种交付链路。商品可同时支持多种履约方式，下单时选择履约方式，订单内商品须支持所选方式。按一期、二期分阶段落地。

## 背景

当前订单系统通过 `Orderable` 契约统一处理可下单主体（商品规格 `Sku`、身份订阅 `Identity` 等）的校验、定价、库存扣减与回退。但交付链路是硬编码的：

- `Sku`（实体商品）走完整物流链路：运费模板计算 → 发货 → 签收；
- `Identity`（虚拟权益）无库存、无物流，靠 `needsReturn()` 返回 `false` 区分退款是否退货。

业务需要更显式的履约方式定义： **一个商品可同时支持多种履约方式**（如既支持快递邮寄、也支持门店自提），下单时由用户选择一种履约方式，订单内所有商品必须支持所选方式。同时为后续新增可下单模型（充值套餐、预约项目等）提供统一扩展点。

## 类型定义

新增枚举 `App\Enums\Mall\FulfillmentType`：

| 枚举值    | 标签     | 运费       | 物流发货               | 库存扣减        | 订单链路               |
|-----------|----------|------------|------------------------|-----------------|------------------------|
| `mail`    | 快递邮寄 | 按运费模板 | 需要（order_shipping） | 正常扣减        | 现有完整链路           |
| `pickup`  | 门店自提 | 免运费     | 不需要                 | 正常扣减        | 待付款 → 待自提 → 核销 |
| `virtual` | 虚拟商品 | 免运费     | 不需要                 | 可不扣（no-op） | 待付款 → 完成          |

> 说明：一期不引入「同城配送」。后续需要时只需在枚举中补充 case 并增加对应运费/物流分支，不影响本方案结构。

## 可下单主体（Orderable）现状

| 模型            | 说明                 | 履约方式归属                                                                          |
|-----------------|----------------------|---------------------------------------------------------------------------------------|
| `Mall\Sku`      | 商品规格（实体）     | 委托商品：`in_array($type, $product->fulfillment_type)`，**仅支持 `mail` / `pickup`** |
| `User\Identity` | 身份订阅（虚拟权益） | 仅支持 `virtual`                                                                      |

---

## 核心设计

### 数据模型

| 层级 | 字段                        | 取值                                  | 说明                                                                                               |
|------|-----------------------------|---------------------------------------|----------------------------------------------------------------------------------------------------|
| 商品 | `products.fulfillment_type` | jsonb 数组，如 `["mail", "pickup"]`   | 该商品支持的全部履约方式，可多选；**商品仅可选 `mail` / `pickup`，`virtual` 仅用于身份等虚拟权益** |
| 订单 | `orders.fulfillment_type`   | 单值（`mail` / `pickup` / `virtual`） | 下单时用户所选的一种方式，落库快照                                                                 |

### 下单流程

1. 用户从商品支持的履约方式集合中选择一种（前端按商品支持集合渲染可选项）；
2. 下单接口接收所选履约方式，遍历订单项校验 `supportsFulfillmentType()`， **任一商品不支持则拒绝整单**；
3. 校验通过后创建订单，`orders.fulfillment_type` 落所选值；
4. 运费计算：订单级类型为 `mail` 才按运费模板计费，`pickup` / `virtual` 免运费。

### 混合订单处理（已确认：方案 A）

购物车同时含「仅支持邮寄的商品」与「仅支持虚拟的商品」时，下单只能选一种方式，任一方式都无法同时满足两类商品。 **已确认采用方案 A：禁止混合，整单选一种。**

| 已确认项      | 结论                                                                                                                               |
|---------------|------------------------------------------------------------------------------------------------------------------------------------|
| 混合订单策略  | **A. 禁止混合**：订单内所有商品必须都支持所选方式，不满足则拒绝下单                                                                |
| 下单接口      | `createOrders` / `createOrder` **新增 `FulfillmentType` 参数**，由 CartController / OrderController / IdentityService 传入所选方式 |
| 校验失败策略  | **整单拒绝**：任一订单项不支持所选方式即抛异常，不做剔除或部分兼容                                                                 |
| 迁移方式      | **直接修改源迁移文件**（products / orders），不新建增量迁移                                                                        |
| 前端渲染      | **一期纳入**：商品详情 / 购物车 API 返回 `fulfillment_type` 支持集合，供前端渲染可选履约方式                                       |
| 订单展示      | **一期纳入**：后台订单列表/详情、前端订单 API 展示/返回履约方式                                                                    |
| Identity 下单 | **固定 `FulfillmentType::Virtual`**：IdentityService 下单直接传入，前端不可选                                                      |

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

- 新增履约方式 **CheckboxList 多选**（默认 `["mail"]`）， **选项仅 `mail` / `pickup`，不提供 `virtual`**（虚拟商品仅用于身份等虚拟权益）；
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

二期目标：让订单状态机、退款流程与核销体系按履约方式差异化流转。涉及跨模块改动，独立评审、独立排期。

### 1. 订单状态机分支（`OrderStatus`）

#### 1.1 新增状态

在 `app/Enums/Mall/OrderStatus.php` 中新增两个状态（保持现有 `mail` 链路状态不变）：

| 新枚举 | 值 | 标签 | 颜色 | 用途 |
|--------|-----|------|------|------|
| `PickupPending` | `pickup_pending` | 待自提 | `amber` | 自提订单付款后进入，等待用户到店核销 |
| `Verified` | `verified` | 已核销 | `teal` | 商家核销通过，等价于 mail 链路的「已签收」 |

#### 1.2 各履约方式状态链路

| 履约方式 | 状态链路 | 说明 |
|----------|----------|------|
| `mail` | `Pending → Paid → Preparing → Delivered → Signed → Completed` | 现有链路完全不变，不回归 |
| `pickup` | `Pending → Paid → PickupPending → Verified → Completed` | 付款后跳过备货/发货/签收，直接待自提；核销后完成 |
| `virtual` | `Pending → Paid → Completed` | 付款成功即完成，无中间状态 |

> 三个链路共享 `Pending` / `Canceled` / `Paid` / `Completed`，差异只在付款后的中间段。

#### 1.3 状态机映射调整（`previous()` / `next()`）

```php
// next() 按履约方式分支（FulfillmentType 作为额外参数传入）
public function next(?FulfillmentType $fulfillmentType = null): array
{
    return match ($this) {
        self::Pending => [self::Canceled, self::Paid],
        self::Paid => match ($fulfillmentType) {
            FulfillmentType::Pickup => [self::PickupPending],
            FulfillmentType::Virtual => [self::Completed],
            default => [self::Preparing, self::Delivered, self::PartiallyShipped],
        },
        self::PickupPending => [self::Verified],
        self::Verified => [self::Completed],
        // mail 链路其余状态映射保持不变
        self::Preparing => [self::Delivered, self::PartiallyShipped],
        self::PartiallyShipped => [self::Delivered, self::Signed],
        self::Delivered => [self::Signed],
        self::Signed => [self::Completed],
        default => [],
    };
}

// previous() 对应补充 PickupPending / Verified 的前驱
```

> `HasStateMachine::canTransitionTo()` 已支持透传额外参数（`...$args`），`OrderStatus` 的 `next(?FulfillmentType)` 直接复用该机制；`OrderService::assertCan()` 需把订单的 `fulfillment_type` 传入校验。

#### 1.4 `OrderService` 状态流转方法分流

| 方法 | 现状 | 二期调整 |
|------|------|----------|
| `pay()` | 统一 `Pending → Paid` | 付款后按订单履约方式自动推进：`pickup → PickupPending`、`virtual → Completed`（并派发对应事件）、`mail` 保持 `Paid` |
| `preparing()` / `deliver()` / `sign()` | mail 专用 | 增加前置校验：非 mail 订单调用时报错或按钮隐藏 |
| `complete()` | mail 链路 | pickup 订单在 `Verified` 后调 `complete()` 自动落 `Completed` |
| 新增 `verify(Order, user)` | - | pickup 专用：`PickupPending → Verified`，校验核销码、记录核销人/时间，派发 `OrderVerified` 事件 |

#### 1.5 事件与通知

新增 `OrderVerified`（核销成功）事件；`OrderCompleted` 在 virtual 订单支付成功、pickup 订单核销后复用现有派发链路，确保结算/积分/发票等下游逻辑（`ShouldSettlement`）正常触发。

#### 1.6 后台与前端展示

- 后台订单列表/详情：履约方式列已在一期就位，二期补充状态徽章颜色与筛选（按履约方式 + 状态组合过滤）；
- 前端订单 API：`OrderResource` 增加 `pickup_code`（核销码）、`pickup_point`（自提点）字段；
- 订单状态机测试：为三个链路各写状态流转测试（`OrderStatusTest` 风格）。

### 2. 自提核销体系

#### 2.1 自提点/门店数据

**新增 `pickup_points` 表**（`Mall\PickupPoint` 模型），字段设计：

| 字段 | 类型 | 说明 |
|------|------|------|
| `id` | bigint PK | |
| `tenant_id` | bigint FK | 所属租户（`$table->tenant()`） |
| `name` | string | 自提点名称（如「中心店」） |
| `contact` | string nullable | 联系人 |
| `phone` | string(32) nullable | 联系电话 |
| `province_id` / `city_id` / `district_id` | bigint nullable | 行政区划（复用 `regionAddress()` 宏） |
| `address` | string | 详细地址 |
| `remark` | string nullable | 备注 |
| `status` | bool | 是否启用（`easyStatus()`） |
| `sort` | int | 排序（`sort()`） |
| `timestamps` + `softDeletes` | | |

- 迁移：直接在 `database/migrations/0003_02_00_000001_create_orders_table.php` 中新增 `pickup_points` 表（二期不新建增量迁移，与原文件操作偏好一致）；
- Filament：Tenant 面板新增「自提点管理」Resource（列表 + 表单 + 软删除），Backend 面板只读查看；
- 商品维度：`products` 表可加 `pickup_point_id`（可选，指定默认自提点）或在下单时由用户选择——**待确认**，默认方案为下单时选择。

#### 2.2 核销码生成

- **生成时机**：`OrderService::pay()` 将 pickup 订单推进到 `PickupPending` 时生成；
- **格式**：`pickup_` 前缀 + 12 位大写字母数字（去易混淆字符 `0/O/1/I`），如 `PICK-3FK8-9X2Q-7M5T`，保证可读、可口头报号；
- **唯一性**：`orders` 表新增 `pickup_code` 字段（unique index，nullable，仅 pickup 订单有值）；
- **幂等**：`pay()` 在事务内检查已有核销码则复用，避免重复生成。

#### 2.3 订单与自提点关联

- `orders` 表新增 `pickup_point_id`（nullable FK，仅 pickup 订单有值）；
- 下单时（`createOrder`）当 `fulfillmentType === Pickup` 时校验自提点存在且启用，落库快照；
- `OrderResource` 返回 `pickup_code`、`pickup_point`（名称/地址）字段；
- 后台订单详情 Infolist：pickup 订单展示「自提点」「核销码」区块。

#### 2.4 商家端核销

- **核销入口**：Tenant 面板订单列表/详情新增 `OrderVerifyAction`（核销按钮，pickup 订单且状态为 `PickupPending` 时可见）；
- **核销流程**：核销码输入（或扫码）→ 校验订单状态与核销码匹配 → `PickupPending → Verified` → 记录核销人（`Auth::user()`）、核销时间（`verified_at`，orders 新增字段）；
- **核销码校验失败**：核销码不存在 / 订单非待自提状态 / 核销码与订单不匹配 → 拒绝并提示；
- **前端用户端**：订单详情展示核销码（二维码图片 + 明文），到店出示给商家核销；
- **防复用**：`Verified` 为终态分支，核销成功后状态机不允许回退，天然防重复核销。

#### 2.5 相关字段汇总（orders 表新增）

| 字段 | 类型 | 说明 |
|------|------|------|
| `pickup_code` | string(32) nullable unique | 核销码 |
| `pickup_point_id` | bigint nullable FK | 自提点 ID |
| `verified_at` | timestamp nullable | 核销时间 |

> 二期 orders 三个字段直接在源迁移文件 `database/migrations/0003_02_00_000001_create_orders_table.php` 中新增，与一期字段同文件，不新建增量迁移。

### 3. 退款物流区分（`Refundable`）

#### 3.1 按履约方式决定退款类型

`Refundable::needsReturn()` 与履约方式对齐：

| 履约方式 | `needsReturn()` | 退款类型 | 说明 |
|----------|-----------------|----------|------|
| `mail` | `true` | `ReturnRefund`（退货退款） | 走完整退货链路：`Pending → WaitingReturn → Shipping → Received → Processing → Completed` |
| `pickup` | 视退款原因 | 两种均可 | 未取货可「仅退款」；已核销后质量问题需退货（与 mail 相同退货链路） |
| `virtual` | `false` | `OnlyRefund`（仅退款） | `Pending → Processing → Completed`，无需寄回（`Identity` 已实现为撤销身份） |

> `RefundStatus` 的 `previous()`/`next()` 已支持按 `RefundType` 分支（`...$args` 透传），二期无需改状态机，只需在创建退款时正确推导 `RefundType`。

#### 3.2 `Sku` 的 `needsReturn()` 细化

当前 `Sku::needsReturn()` 恒为 `true`。二期改为按商品履约方式推导：

```php
public function needsReturn(): bool
{
    return $this->product->needsShipping(); // 仅支持快递邮寄时需退货
}
```

#### 3.3 退款创建入口按履约方式分流

- `CreateRefundAction` / 退款服务创建退款时，按订单 `fulfillment_type` + 订单项履约能力推导默认退款类型：
  - `virtual` → 强制 `OnlyRefund`（拒绝选择退货退款）；
  - `mail` → 默认 `ReturnRefund`；
  - `pickup` → 默认 `OnlyRefund`（未核销场景），已核销后允许 `ReturnRefund`。
- 校验：`virtual` 订单项不允许创建退货退款，接口/表单层拦截。

#### 3.4 退款资源回收不变

`Refundable::refund()` 的回收逻辑（SKU 回退库存、Identity 撤销身份）沿用现有实现，与履约方式解耦。

### 4. 下单校验强化

#### 4.1 收货地址校验

| 履约方式 | 收货地址 | 校验策略 |
|----------|----------|----------|
| `mail` | 必填 | 沿用现有 `OrderAddressRule` |
| `pickup` | 不填 | 下单校验自提点必选且启用；`OrderRequest` / `OrderFromCartRequest` 增加 `pickup_point_id` 校验规则 |
| `virtual` | 不填 | 拒绝传地址（或忽略）；校验所选方式被所有商品支持 |

#### 4.2 下单参数校验补充

- `OrderRequest` / `OrderFromCartRequest`：新增 `pickup_point_id`（`fulfillment_type === pickup` 时 `required`，用 `Rule::when()` 条件校验）；
- 服务层兜底：`createOrder` 中 `fulfillmentType === Pickup` 时校验自提点存在且启用，`fulfillmentType === Virtual` 时校验订单项均为虚拟权益。

#### 4.3 前端交互

- 下单页按商品支持集合渲染可选履约方式（一期已返回 `fulfillment_types`），选择 `pickup` 时展示自提点选择器，选择 `mail` 时展示收货地址表单，选择 `virtual` 时隐藏地址；
- 混合校验：所选方式任一商品不支持时前端即时提示，后端仍整单拒绝兜底。

#### 4.4 校验测试

- 覆盖：`mail` 无地址拒绝、`pickup` 无自提点拒绝、`virtual` 传地址拒绝、所选方式不被商品支持拒绝。

---

## 二期任务拆分与排期

按依赖关系拆分为 3 个可独立评审/交付的子任务（M2.1 → M2.2 → M2.3）：

| 子任务 | 内容 | 依赖 | 关键交付 |
|--------|------|------|----------|
| **M2.1 状态机分支** | `OrderStatus` 新增 `PickupPending`/`Verified`，`next()/previous()` 按履约方式分支，`OrderService::pay()` 按类型自动推进，新增 `verify()` 与 `OrderVerified` 事件 | 一期（已完成） | 三链路状态流转可用，`virtual` 支付即完成 |
| **M2.2 自提核销体系** | `pickup_points` 表 + Filament 管理、`pickup_code`/`pickup_point_id`/`verified_at` 字段、核销码生成、`OrderVerifyAction` 核销入口 | M2.1 | 自提点管理 + 订单核销闭环 |
| **M2.3 退款与校验强化** | `Sku::needsReturn()` 按履约方式推导、退款创建按类型分流、下单地址/自提点条件校验、前端交互 | M2.1、M2.2 | 退款链路按履约方式正确流转 |

## 二期验收标准

- **状态机**：`OrderStatusTest` 覆盖三条链路全流转与非法跳转拒绝（`canTransitionTo()`）；
- **核销**：pickup 订单付款后生成唯一核销码；核销成功 `PickupPending → Verified → Completed`；重复核销被拒绝；核销人与时间落库；
- **虚拟直连**：virtual 订单支付成功即 `Completed`，`ShouldSettlement` 下游结算正常触发；
- **退款**：virtual 订单项无法创建退货退款；pickup 未核销默认仅退款；mail 默认退货退款且状态机按 `RefundType` 分支正确；
- **下单校验**：pickup 必选自提点、virtual 拒传地址、mail 必填地址，均有测试覆盖；
- **回归**：mail 链路全流程（含部分发货）不受影响，现有订单/物流/退款测试通过；
- 全部改动通过 Pint 与静态检查。

---

## 涉及文件清单

### 一期（已完成）

| 文件 | 改动 |
|------|------|
| `app/Enums/Mall/FulfillmentType.php` | 新增枚举 |
| `app/Contracts/Orderable.php` | 新增 `supportsFulfillmentType()` |
| `app/Models/Mall/Sku.php` | 实现契约方法 |
| `app/Models/User/Identity.php` | 实现契约方法 |
| `database/migrations/0003_01_00_000001_create_products_table.php` | products 加 jsonb 多值字段 |
| `database/migrations/0003_02_00_000001_create_orders_table.php` | orders 加单值字段 |
| `app/Models/Mall/Product.php` | casts + 辅助方法 |
| `app/Models/Mall/Order.php` | casts |
| `app/Filament/Tenant/Clusters/Mall/Resources/Products/Schemas/ProductForm.php` | 多选 + 联动 |
| `app/Services/Mall/OrderService.php` | 下单参数 + 校验 + 落所选值 |
| `app/Services/Mall/DeliveryService.php` | 按所选方式判断运费 |
| `app/Http/Requests/Mall/OrderRequest.php` / `OrderFromCartRequest.php` | `fulfillment_type` 校验 |
| `app/Http/Resources/Mall/ProductResource.php` / `CartItemResource.php` / `OrderResource.php` | 履约方式返回 |
| `app/Filament/*/Orders/`（表格 + 详情） | 履约方式展示 |
| `tests/` | 下单校验 + 运费计算测试 |

### 二期

| 子任务 | 文件 | 改动 |
|--------|------|------|
| M2.1 | `app/Enums/Mall/OrderStatus.php` | 新增 `PickupPending`/`Verified` + 按履约方式分支 |
| M2.1 | `app/Services/Mall/OrderService.php` | `pay()` 按类型推进、新增 `verify()`、`assertCan()` 传履约方式 |
| M2.1 | `app/Events/Mall/OrderVerified.php` | 新增核销事件 |
| M2.2 | `database/migrations/0003_02_00_000001_create_orders_table.php` | 新增 `pickup_points` 表（直接改源文件） |
| M2.2 | `database/migrations/0003_02_00_000001_create_orders_table.php` | orders 新增 `pickup_code`/`pickup_point_id`/`verified_at`（直接改源文件） |
| M2.2 | `app/Models/Mall/PickupPoint.php` | 自提点模型（含 casts/关系/scope） |
| M2.2 | `app/Models/Mall/Order.php` | 新增 casts/关系（pickupPoint） |
| M2.2 | `app/Filament/Tenant/Clusters/Mall/Resources/PickupPoints/` | 自提点 Resource（Tenant） |
| M2.2 | `app/Filament/Backend/Clusters/Mall/Resources/PickupPoints/` | 自提点只读 Resource（Backend） |
| M2.2 | `app/Filament/Actions/Mall/OrderVerifyAction.php` | 核销 Action |
| M2.2 | `app/Services/Mall/PickupService.php`（可选） | 核销码生成/校验服务 |
| M2.2 | `app/Http/Resources/Mall/OrderResource.php` | 返回 `pickup_code`/`pickup_point` |
| M2.3 | `app/Models/Mall/Sku.php` | `needsReturn()` 按履约方式推导 |
| M2.3 | `app/Filament/Actions/Mall/CreateRefundAction.php` | 退款类型按履约方式分流 |
| M2.3 | `app/Http/Requests/Mall/OrderRequest.php` / `OrderFromCartRequest.php` | `pickup_point_id` 条件校验 |
| M2.3 | `app/Services/Mall/OrderService.php` | 自提点/地址服务层兜底校验 |
| 全部 | `tests/Feature/Mall/` | 状态机、核销、退款、校验测试 |

---

## 复盘补充（2026-08-14）

对一期实现与二期计划进行系统性复盘，核对实际代码后发现的补充项，纳入二期一并处理：

### 补充项 1：结算预览（`CartController::preview`）需感知履约方式

- **现状**：`preview()` 计算运费时按购物车商品全部计入（`$item->product->delivery_id` 分组），未感知所选履约方式——若用户选择 `pickup`/`virtual`，预览运费仍按 mail 模板计算，与实际下单（`OrderService::calculateOrderFreight` 已按类型短路）不一致；
- **修复**：`CheckoutPreviewRequest` 增加 `fulfillment_type` 参数，`preview()` 在 `fulfillmentType !== Mail` 时直接返回 `freight = '0.00'`，并同步校验所选方式被所有商品支持；
- **归属**：二期 M2.3（下单校验强化）一并落地，避免前后端金额不一致。

### 补充项 2：`OrderAutoCompleteCommand` 需支持 pickup 核销后自动完成

- **现状**：`app:mall:order-auto-complete` 仅扫描 `status = Signed` 的订单，pickup 订单核销后处于 `Verified` 状态不会被自动完成，virtual 订单支付即 `Completed` 不受影响；
- **修复**：命令查询条件按履约方式扩展——`mail` 扫 `Signed`、pickup 扫 `Verified`（核销 `$days` 天后自动 `Completed`），或 pickup 核销后由事件直接推进完成（需业务确认自动完成周期）；
- **归属**：二期 M2.1（状态机分支）落地时同步调整。

### 补充项 3：`OrderCompletedListener` 接入结算/下游逻辑

- **现状**：`app/Listeners/Mall/OrderCompletedListener.php` 为空壳（空 try/catch），`OrderCompleted` 事件虽已派发但未消费；
- **修复**：二期在 listener 中接入 `SettlementService`/结算凭证创建等下游逻辑（`Order` 已实现 `ShouldSettlement`），确保 virtual 支付即完成、pickup 核销后完成都能触发结算；
- **归属**：二期 M2.1（事件与通知）落地时一并实现。

### 二期计划细节补全

| 位置 | 补充内容 |
|------|----------|
| §1.3 | `previous()` 具体映射：`PickupPending` 前驱 `[Paid]`、`Verified` 前驱 `[PickupPending]`、`Completed` 前驱追加 `[Verified]` |
| §2.2 | 核销码二维码生成方案：使用 `simplesoftwareio/simple-qrcode` 或 Filament 前端生成，待实现时确认依赖 |
| §1.4 | 退款中订单状态联动：pickup/virtual 订单退款期间订单保持原状态，退款完成后按 `Refundable` 回收逻辑处理，不新增订单级退款状态 |
| §1.2 | 超时自动取消（`AutoCloseOrder`）：仅 `Pending` 状态可取消，对 pickup/virtual 无影响（付款后即离开 Pending），无需改动 |

---

## 三期规划（暂缓，按需立项）

经复盘评估，**不新增完整三期**。当前业务已确认排除同城配送与按履约方式拆单；复盘发现的补充项均并入二期。以下为暂缓候选，业务需要时单独立项：

| 候选 | 内容 | 触发条件 |
|------|------|----------|
| 商品虚拟化（卡密自动发货） | 商品侧放开 `virtual`，卡密/兑换码生成与自动发放 | 出现纯虚拟商品（非身份类）销售需求时 |
| 预售/预约/拼团履约 | Campaign 模块扩展，新增预售、到店预约等履约类型 | 营销玩法需要预约/预售履约链路时 |
| 履约中心 OMS | 多履约方式统一运营视图、按类型分报表 | 履约类型增多、运营需要统一调度时 |
| 物流轨迹深化 | 快递 100 等物流轨迹接入（mail 链路） | 与履约方式正交，独立需求立项 |

> 若未来放开商品虚拟化或引入新履约类型，需按本期「枚举扩展 + 状态机分支 + 下单校验 + 展示」四件套流程复用推进。
