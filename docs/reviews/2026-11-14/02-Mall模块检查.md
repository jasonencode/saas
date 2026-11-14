# Mall 模块 检查报告

- **检查日期**：2026-11-14
- **检查范围**：`app/Models/Mall/**`、`app/Services/Mall/**`、`app/Http/{Controllers,Requests,Resources}/Mall/**`、`app/Policies/Mall/**`、`app/Enums/Mall/**`、`routes/apis/mall.php`、Mall 相关迁移、`app/Filament/**` 中 Mall 资源/表单/表格/动作、`tests/Feature/Mall/**`
- **检查方式**：逐文件静态走读 + 关键链路推演（下单 → 支付 → 发货 → 签收 → 退款）+ 迁移与 Filament 5 源码交叉核对
- **结论**：订单/退款 Action 分层与金额字符串化（bcmath）已明显改善；但**资金链仍有断点**（后台代付不建支付单，订单无法退款），**发货/退款数量口径缺 `shipped_qty`**（部分退款后超发或永久卡在 PartiallyShipped），后台删除与公共接口仍绕过领域服务/租户上下文。

**统计：严重 1 / 高 6 / 中 12 / 低 3（共 22 条）。**

---

## 一、模块概览

| 子域 | 现状 | 主要风险 |
|---|---|---|
| 订单 | Facade `OrderService` + 每状态一个 Action，`before/handle/log/after` 分层；金额已统一字符串 | 后台代付绕过支付单；删除绕过 Service；支付事务内发通知 |
| 发货/售后 | `Deliver`/`CancelDeliver` 按明细行数判定完成；退款 `Apply/Process/Complete` 较完整 | 无已发数量字段，部分退款后超发或卡死 |
| 商品/SKU | 多态 `orderable`、库存条件扣减 | 软删 SKU 导致订单/代退款 500；`sale` 从不累加 |
| 店铺配置 | `store_configures` 以 `tenant_id` 为主键 | `fill($data)` 可改主键；关店后下单不受限 |
| 公共接口 | 自提点/退货地址免登录 | 返回全平台联系人手机号与详细地址 |

---

## 二、严重问题

### MALL-01 ｜ 严重 ｜ 资金 / 一致性

**后台「订单付款」扣余额但不创建支付单，订单永久无法退款**

- **位置**：`app/Filament/Actions/Mall/OrderPaymentAction.php:102-112`、`app/Services/Mall/RefundActions/Process.php:108-120`
- **代码要点**：事务内只调用 `UserAccountService::modifyAsset(..., -totalAmount)` 再 `OrderService::pay()`；全仓库 `PaymentOrder` 仅在 `PaymentController:74` 创建。
- **失败场景**：该订单走售后时 `paymentOrders()->where('status', Paid)` 为空 → 抛「没有可退款的支付单」，用户/商家/自提超时退款都无法打款；结算侧也没有支付单可对账。
- **修复建议**：改为走 `PaymentService` 先建 `PaymentOrder` 再 `payByBalance`，或在本 Action 内补建 Paid 支付单后再 `OrderService::pay()`。

---

## 三、高优先级问题

### MALL-02 ｜ 高 ｜ 发货 / 数据一致性

**部分退款后发货：API 超发，后台整行剔除 → 订单永久「部分发货」**

- **位置**：`app/Services/Mall/OrderActions/Deliver.php:77-79,93-102`、`app/Filament/Actions/Mall/OrderShipAction.php:88,154-161`
- **失败场景**：明细 `qty=5`、已退 2：API 侧仍按整行发 5 件（超发 2 件）；后台发货列表要求「整行可发」，该行被剔除，剩余 3 件永远发不出。完成判定用明细行数，全退明细也计入 `totalItems` → `shippedItems` 恒小于总数，订单永远 `PartiallyShipped`，`auto_sign_at` 不写、自动签收/完成失效。
- **修复建议**：`order_items` 增加 `shipped_qty`（或发货明细分表），可发 = `qty - refunded_qty - shipped_qty`；完成判定改为数量口径，UI 与 Service 共用。

### MALL-06 ｜ 高 ｜ 后台 / 数据删除

**订单删除/批量删除/永久删除绕过 `OrderService`**

- **位置**：`app/Filament/Tenant/Clusters/Mall/Resources/Orders/Tables/OrdersTable.php:130-139`、`Pages/ViewOrder.php:54-55`；对照 `app/Services/Mall/OrderActions/Delete.php:55-60`
- **失败场景**：删除待付款订单不释放已核销券/已扣库存；强删后 `order_items`、`refunds`、`event_logs`、`payment_orders.paymentable` 变成孤儿（无外键），售后与财务对账不可恢复。
- **修复建议**：删除统一走 `OrderService::delete`；移除 `ForceDeleteBulkAction`（或仅系统账号 + 二次审批）；`Order::deleting` 统一幂等释放资源。

### MALL-27 ｜ 高 ｜ 空指针 / API

**无收货地址订单（虚拟/自提）详情 500**

- **位置**：`app/Http/Resources/Mall/OrderResource.php:28,30`、`app/Http/Resources/Mall/OrderAddressResource.php:15-18`
- **代码要点**：`OrderAddressResource::make($this->resource->address)` 未判空；`toArray()` 直接读 `name/mobile/address`。
- **失败场景**：虚拟/自提订单 `address=null` → 「Attempt to read property on null」→ `GET /mall/orders/{no}` 500；所有带 `OrderResource` 的输出（库存、退款）同受影响。现有测试因 PHPUnit 接管错误处理器未暴露。
- **修复建议**：`whenNotNull(...)` 或 `?->` + 空数组兜底，补「无地址订单详情」用例。

### MALL-07 ｜ 高 ｜ 空指针 / 售后

**SKU/商品软删后订单详情与「代用户退款」500**

- **位置**：`app/Http/Resources/Mall/OrderItemResource.php:36-49`、`app/Filament/Actions/Mall/CreateRefundAction.php:193-208`（202 行）
- **失败场景**：商家下架并删除 SKU 后，历史订单详情与租户代退款弹窗渲染 500，售后被阻断；`Apply::assertRefundItems` 已有 `?->` 兜底，口径不一致。
- **修复建议**：使用 `$orderable?->`，回退 `orderable_name/orderable_type` 快照；补软删 SKU 回归用例。

### MALL-04 ｜ 高 ｜ 越权 / 主键

**店铺配置 `fill($data)` 含客户端可控 `tenant_id`**

- **位置**：`app/Filament/Tenant/Clusters/Mall/Pages/Configure.php:77-78,182-191`、`app/Models/Mall/StoreConfigure.php:17-20`
- **失败场景**：篡改 Livewire 状态把 `tenant_id` 改成他人 ID：目标不存在则把自己的配置「搬」到他人租户；目标存在则唯一键冲突 500。
- **修复建议**：`Arr::except($data, ['tenant_id'])`，用 `updateOrCreate(['tenant_id' => Filament::getTenant()->getKey()], ...)`。

### MALL-05 ｜ 高 ｜ 隐私 / 租户隔离

**公共接口返回全平台自提点/退货地址的 PII**

- **位置**：`app/Http/Controllers/Mall/PickupPointController.php:20-22`、`ReturnAddressController.php:20-23`、对应 Resource、`routes/apis/mall.php:125,130`
- **失败场景**：未登录即可爬取全平台仓库联系人手机号与详细地址；结算页还可能拉到非本租户自提点，下单时校验失败。
- **修复建议**：接口要求 `tenant_id`（或由商品/店铺推导）并 `->ofTenant()`，缺参 422。

---

## 四、中优先级问题

### MALL-28 ｜ 中 ｜ 授权 / 运行时错误

**核销页 `canAccess` 把类名字符串传给强类型策略**

- **位置**：`app/Filament/Tenant/Clusters/Mall/Pages/PickupVerify.php:70-73`、`app/Policies/Mall/OrderPolicy.php:202-210`
- **失败场景**：`userCan('orderVerify', Order::class)` → 策略收到字符串，非系统角色（`Policy::before()` 未放行）访问核销页直接 TypeError 500。
- **修复建议**：改为无实例的 `orderVerifyAny()`，或传 `new Order`。

### MALL-29 ｜ 中 ｜ 业务约束

**关店后仍可下单，`store.opened` 中间件未挂任何路由**

- **位置**：`bootstrap/app.php:36`、`routes/apis/mall.php:23-26`、`app/Services/Mall/OrderActions/CreateOrder.php:93-100`
- **失败场景**：关店后商品接口照常返回、`POST /mall/orders` 仍建单扣库存；该中间件依赖 API 租户上下文，直接挂载会恒 403。
- **修复建议**：在 `CreateOrder` 内校验 `storeConfigure->isOpened()`（与租户上下文一并解决），或删除死中间件。

### MALL-30 ｜ 中 ｜ 资金 / 审计

**改退款金额与退款明细脱钩**

- **位置**：`app/Services/Mall/RefundActions/UpdateAmount.php:137-163`、`app/Filament/Actions/Mall/UpdateRefundAmountAction.php:38-54`
- **失败场景**：1 件商品的退款单可把 `goods_amount` 改成整单金额并通过，打款按新金额执行，明细仍只有 1 件，审计无法还原。
- **修复建议**：校验 `goods_amount ≤ Σ refund_items 净额 + 尾差`，或只允许下调/改运费；记录改额前后明细。

### MALL-31 ｜ 中 ｜ 身份权益

**身份订单退款清空用户全部身份权益**

- **位置**：`app/Models/User/Identity.php:240-248`、`app/Services/Mall/RefundActions/Complete.php:74-80`
- **失败场景**：用户已有 1 年身份，退 1 个月续费单 → `detach` 把 1 年权益一起删；退部分数量同样清空。
- **修复建议**：按 `qty × days` 回退 `end_at`，只在无其他有效订单时 detach，并保留 `IdentityService` 日志。

### MALL-08 ｜ 中 ｜ 业务口径

**用户侧退货退款永远退不到运费**

- **位置**：`app/Http/Controllers/Mall/RefundController.php:44-60`、`app/Http/Requests/Mall/RefundRequest.php:17-48`、`app/Services/Mall/RefundActions/Apply.php:566-580`
- **失败场景**：请求无 `freight_amount`，`RefundData` 默认 0，退货退款运费恒 0；运营端却有退运费入口，口径不一致。
- **修复建议**：补请求字段并限制 ≤ 可退运费，或产品上明确不退并删除运营端口径。

### MALL-09 ｜ 中 ｜ 越权 / fail-open

**`AuthorizesModelAccess` 在订单用户为空时放行**

- **位置**：`app/Http/Controllers/Traits/AuthorizesModelAccess.php:20-31`、`app/Http/Controllers/Mall/RefundController.php:40,136,154,178`
- **失败场景**：订单用户被软删后，任意登录用户可查看/取消/签收/删除该订单（含地址与手机号）；RefundController 还会对 null 调用 `isNot()` 500。
- **修复建议**：改为 `abort_unless($model->user?->is($user), 404)`；Controller 统一 `?->`。

### MALL-13 ｜ 中 ｜ 事务副作用

**支付事务内发通知/派发事件，失败即回滚已支付**

- **位置**：`app/Services/Mall/OrderActions/Pay.php:52-103`（93、95-100）
- **失败场景**：通知通道异常 → 订单状态、库存、余额、支付单全部回滚，渠道侧已收款需人工对账。
- **修复建议**：通知与事件移到 `DB::afterCommit()`，或 try/catch + `report()`。

### MALL-15 ｜ 中 ｜ 推荐

**个性化推荐恒为 0（按 `Product` 查 `orderable_type`，实际是 `Sku`）**

- **位置**：`app/Services/Mall/RecommendationService.php:175`、`CreateOrder.php:137-145`
- **修复建议**：改查 `Sku::class` 并按 `orderable.product.category_id` 聚合。

### MALL-14 ｜ 中 ｜ 数据口径

**`skus.sale` 销量从不累加**

- **位置**：`app/Models/Mall/Sku.php:147-157`、`app/Models/Mall/Product.php:330-337`
- **失败场景**：销量排序、推荐热度、`total_sale` 展示全部失真。
- **修复建议**：支付成功累加、退款完成回退（事务内），或按已支付订单实时聚合。

### MALL-16 ｜ 中 ｜ 批量操作

**批量审核/批量退款无事务、无逐条结果汇总**

- **位置**：`app/Filament/Actions/Mall/ApproveRefundBulkAction.php:53-78`、`ConfirmRefundBulkAction.php:48-73`
- **失败场景**：第 3 条失败时前 2 条已成功，界面却提示整批失败；重试易重复处理。
- **修复建议**：逐条收集成功/失败并汇总提示。

### MALL-17 ｜ 中 ｜ 空指针

**购物车 SKU 被删后结算预览/下单 500**

- **位置**：`app/Http/Controllers/Mall/CartController.php:112-118,232-238`
- **修复建议**：`$item->sku?->...`，过滤失效行并提示；调用已有的 `CartService::validateCartItems`。

### MALL-03 ｜ 中 ｜ 租户隔离（依赖框架）

**核销查询与 `Verify` 服务无显式租户断言**

- **位置**：`app/Filament/Tenant/Clusters/Mall/Pages/PickupVerify.php:89-91`、`app/Services/Mall/OrderActions/Verify.php:65-83`
- **说明**：当前靠 Filament 5 模型级租户全局作用域隐式兜底；一旦页面改 `withoutGlobalScopes`/`withTrashed` 或服务被队列/命令复用，即可跨租户核销。
- **修复建议**：查询显式 `where('tenant_id', Filament::getTenant()->getKey())`，`Verify` 增加租户一致性断言。

---

## 五、低优先级问题

- **MALL-11**：`OpenStoreAction.php:28,35` 两次 `visible()` 后者覆盖前者，无 `openStore` 权限也能开通商城；且开通无审核/无 `event_logs`。
- **MALL-32**：租户订单列表 `refund_status` 列逐行查 `refunds()`，100 行多 100 次查询。
- **MALL-33**：结算预览的包邮门槛依赖 `method_exists($item, 'getAmount')`，`CartItem` 无此方法 → 预览永远不包邮，与实单运费不一致。

---

## 六、旧问题状态（2026-11-01 → 2026-11-14）

| 旧编号 | 状态 | 说明 |
|---|---|---|
| MALL-01 | 仍存在 | 后台代付仍不建支付单 |
| MALL-02 | 仍存在（形态变化） | 新增「全退不可发」，但仍整行粒度、无 `shipped_qty` |
| MALL-03 | 有变化（降级） | Filament 模型级全局租户作用域隐式兜底，服务层仍无断言 |
| MALL-04 | 仍存在 | `fill($data)` 含 `tenant_id` |
| MALL-05 | 仍存在 | 公共接口无租户、免登录、输出 PII |
| MALL-06 | 仍存在 | 删除/强删绕过 OrderService |
| MALL-07 | 仍存在 | `orderable` 空安全仅补了 Filament 一处 |
| MALL-08 | 仍存在 | 用户侧退运费恒 0 |
| MALL-09 | 仍存在 | `AuthorizesModelAccess` fail-open |
| MALL-10 | 仍存在 | `refunds.no` 无唯一约束 |
| MALL-11 | 仍存在 | 双 `visible()` 覆盖 |
| MALL-12 | 有变化（隐式修复） | Tab 徽章仍裸查询，但受全局作用域保护 |
| MALL-13 | 仍存在 | 通知/事件在支付事务内 |
| MALL-14 | 仍存在 | `sale` 不累加 |
| MALL-15 | 仍存在 | 推荐查错多态类型 |
| MALL-16 | 仍存在 | 批量操作无汇总 |
| MALL-17 | 仍存在 | `cartItem.sku` 空安全 |
| MALL-18 | 仍存在 | 购物车 `firstOrCreate` 竞态 |
| MALL-19 | **已修复** | 全额退款尾差随最后一件退出 |
| MALL-20 | 仍存在 | 快递单号 UI 无 `maxLength(32)` |
| MALL-21 | 仍存在 | 未校验 `pickup_point_product` |
| MALL-22 | 仍存在 | `OrderResult` DTO 死代码 |
| MALL-23 | 仍存在 | 删除策略与服务口径不一致 |
| MALL-24 | **已修复** | 金额统一 bcmath 字符串 |
| MALL-25 | **已修复** | `DeliveryService` 兼容 stdClass |
| MALL-26 | **已修复** | 订单/退款单号业务前缀 |

---

## 七、修复优先级

1. **P0**：MALL-01（退款断链）、MALL-02（超发/卡死）、MALL-27（订单详情 500）、MALL-06（资金数据可删）。
2. **P1**：MALL-04（跨租户主键）、MALL-05（PII 公网）、MALL-07（软删 SKU 500）、MALL-28（核销页 500）。
3. **P2**：MALL-29/30/31/08/09（业务口径与越权）、MALL-13（事务副作用）。
4. **P3**：MALL-14/15/16/17/03 与低危项。
