# Mall 模块 检查报告

- **检查日期**：2026-11-01
- **检查范围**：`app/Models/Mall/*`、Mall 相关 Traits、`app/Services/Mall/**`、`app/Http/{Controllers,Requests,Resources}/Mall/*`、`app/Jobs/Mall`、`app/Listeners/Mall`、`app/Notifications/Mall`、`app/Policies/Mall`、`app/Filament/Tenant/Clusters/Mall/**`、`routes/apis/mall.php`、Mall 相关迁移
- **检查方式**：逐文件静态走读 + 迁移/框架源码交叉核对 + 全量测试（`DeliveryServiceTest` 5 条失败、`OrderAmountTest` 2 条失败已定位）
- **结论**：整体架构清晰（**Facade Service + 每状态一个 Action**，`before/handle/log/after` 分层，金额以 bcmath 为主，并发处理在主流程上较认真）。问题集中在**边界与旁路**：后台 Action 绕过领域服务（删除订单、余额代付）、后台资金入口绕过支付单、公共 API 缺租户过滤、UI 与 Service 对「可发/可退」口径不一致。

**统计：严重 1 / 高 7 / 中 13 / 低 5（共 26 条）。**

---

## 一、模块概览

### 模型（27 个）

| 模型 | 职责 |
|---|---|
| `Order` | 订单主体，`ShouldPayment`/`ShouldSettlement`；状态机、金额（`amount + freight - coupon_discount`）、可退运费、券/发票多态 |
| `OrderItem` / `OrderAddress` / `OrderShipping` | 明细（多态 `orderable` + 名称/单价快照）、地址快照、发货单 |
| `Refund` / `RefundItem` / `RefundExpress` | 售后单（`no` 路由键）、退款项、退货物流 |
| `Cart` / `CartItem` | 用户全局购物车（`user_id` 唯一）、SKU 行 |
| `Product` / `Sku` | 商品（身份折扣、观察者、多履约方式）与 SKU（库存条件扣减） |
| `ProductCategory` / `ProductTag` / `ProductDiscount` / `ProductLog` / `Banner` / `Topic` / `Supplier` | 商品周边 |
| `Delivery` / `DeliveryRule` / `Express` | 运费模板与规则、快递公司 |
| `PickupPoint` / `ReturnAddress` / `Region` | 自提点、退货地址、行政区 |
| `StoreApply` / `StoreConfigure` | 开店申请；店铺配置以 `tenant_id` 为主键 |

### 服务

| 类 | 职责 |
|---|---|
| `OrderService` | 订单门面：创建（跨租户拆单）/支付/备货/发货/取消发货/签收/核销/完成/取消/删除/改址/改运费/备注 |
| `RefundService` | 售后门面：申请/代申请/审核/驳回/取消/退货物流/签收/发起退款/完成/失败/重试/重置/改金额/删除 |
| `OrderActions\*`（17 个） | 每状态一个操作，`Create` 负责拆单事务，`CreateOrder` 负责算价/扣库存/核销券 |
| `RefundActions\*`（14 个） | 售后状态流转与资源回收 |
| `CartService` / `DeliveryService` / `ProductService` / `ProductDiscountService` / `StoreService` / `RecommendationService` / `OrderableResolver` | 购物车、运费、商品、折扣、店铺、推荐、可订购主体解析 |
| DTO | `OrderItemDto` / `RefundData` / `RefundItemData` / `OrderResult`（未被引用） |

### 架构模式

1. **Action/Operation**：`AbstractOperation::dispatch()` 固定 `before → handle → log → DB::afterCommit(after)`。
2. **门面 Service**：Service 只转交参数，操作人显式传入，不读 `Auth`。
3. **多态可订购主体**：`Orderable` / `Refundable` 契约把定价、库存、退款回收下放到 `Sku` / `Identity`。
4. **状态机枚举**：`canTransitionTo($target, $fulfillmentType)`，`Paid` 后按 mail/pickup/virtual 分叉。
5. **租户隔离非全局**：`BelongsToTenant` 只提供 `ofTenant` scope，API/Service/Console 必须显式过滤（本期多项问题由此产生）。

---

## 二、严重问题

### MALL-01 ｜ 严重 ｜ 资金 / 一致性
**后台「订单付款」扣余额但不创建支付单，该订单永久无法退款**

- **位置**：`app/Filament/Actions/Mall/OrderPaymentAction.php:84-113`、`app/Services/Mall/RefundActions/Process.php:108-120`

```php
$accountService->modifyAsset(
    account: $account,
    asset: AccountAssetType::Balance,
    amount: -$lockedOrder->getTotalAmount(),
    ...
$orderService->pay($lockedOrder, Filament::auth()->user());
```
```php
$payment = $this->refund->order->paymentOrders()
    ->where('status', PaymentStatus::Paid)
    ->latest('id')
    ->get()
    ->first(...);
if (!$payment) {
    throw new RuntimeException('该订单没有可退款的支付单，无法完成退款');
}
```

**失败场景**：正常链路（`PaymentController → PaymentOrder → PaymentService::payByBalance`）会生成 `payment_orders`，售后退款只能针对 `PaymentOrder` 建 `PaymentRefund`。后台「订单付款」直接扣 `UserAccount` 余额、只留 `user_account_logs`，**不生成支付单**。这类订单一旦要退款（商家代退、用户申请、自提超时自动退款），`Process` 一律抛「没有可退款的支付单」，售后永远打不了款；结算侧也拿不到 `PaymentOrder`。

**建议**：后台代付改走 `PaymentService`（先建 `PaymentOrder` 再 `payByBalance`），或在该 Action 内补建 `PaymentOrder`（状态 Paid、金额=实付）后再 `OrderService::pay()`。

---

## 三、高优先级问题

### MALL-02 ｜ 高 ｜ 状态机 / 库存
**部分退款后发货：服务端按原数量超发，界面整行剔除，订单永久卡在「部分发货」**

- **位置**：`app/Services/Mall/OrderActions/Deliver.php:66-107`、`app/Filament/Actions/Mall/OrderShipAction.php:84-96`

```php
if ($items->contains(fn (OrderItem $item): bool => $item->getShippableQtyAttribute() <= 0)) { ... }

$this->order->items()->whereIn('id', $itemIds)->update([
    'order_shipping_id' => $express->id,
]);

$totalItems = $this->order->items()->count();
$shippedItems = $this->order->items()->whereNotNull('order_shipping_id')->count();
$this->order->status = $shippedItems >= $totalItems ? Delivered : PartiallyShipped;
```
```php
->reject(fn (OrderItem $item) => $item->getMaxRefundCounts() < $item->qty)
```

**失败场景**：明细没有「已发数量」字段，`Deliver` 只给整条明细打 `order_shipping_id`，不扣减已退款数量。明细 qty=5、已退 2：
- API 指定该明细发货 → 5 件全部记入同一运单，**多出 2 件库存被出库**；
- Filament 发货 → `reject(maxRefundCounts < qty)` 把整行剔除，剩余 3 件**永远发不出**；
- `$totalItems` 把整件退款、永不发货的明细也计入，`shippedItems` 永远 `< totalItems`，订单永远 `PartiallyShipped`，不写 `auto_sign_at`，自动签收/自动完成链路整体失效。

**建议**：`order_items` 增加 `shipped_qty`（或独立 `order_item_shipments`），按「可发 = qty − 已退 − 已发」校验并落数量；完成判定改为「所有明细 `shipped_qty + refunded_qty >= qty`」，UI 与 Service 共用口径。

### MALL-03 ｜ 高 ｜ 越权
**自提核销页按核销码全局查单，无租户隔离**

- **位置**：`app/Filament/Tenant/Clusters/Mall/Pages/PickupVerify.php:89-94`、`app/Services/Mall/OrderActions/Verify.php:65-78`

```php
$this->order = Order::with(['items.orderable', 'user', 'pickupPoint'])
    ->where('pickup_code', $code)
    ->first();
```

**失败场景**：Filament 资源查询有租户作用域，但该页面用 `Order::query()` 直接查，没有 `ofTenant()`；`Verify` 只校验履约方式与核销码。租户 A 员工拿到（或被泄露）租户 B 的核销码，即可看到对方订单号、用户、金额、自提点，并直接核销（写 `verified_by`）。

**建议**：查询加 `->where('tenant_id', Filament::getTenant()->getKey())`；`Verify` 内增加订单租户与操作人所属租户一致性断言。

### MALL-04 ｜ 高 ｜ 越权 / 多租户
**店铺配置 `fill($data)` 含隐藏 `tenant_id`，可跨租户改主键**

- **位置**：`app/Filament/Tenant/Clusters/Mall/Pages/Configure.php:77-78`、`:160-171`、`app/Models/Mall/StoreConfigure.php:18-21`

```php
Forms\Components\Hidden::make('tenant_id')
    ->default(Filament::getTenant()->getKey()),
```
```php
$data = $this->form->getState();
$record = $this->getRecord();   // 按当前租户查
if (!$record) { $record = new StoreConfigure; }
$record->fill($data);
$record->save();
```

**失败场景**：`StoreConfigure` 是 `#[Unguarded]` 且 `#[Table(key: 'tenant_id')]`。`$data` 来自 Livewire 公共属性，客户端可改 `tenant_id`；`fill()` 会把主键改成别的租户，`save()` 以原主键为 WHERE、把 `tenant_id` 写成他人 ID（冲突则 500，能写入则把自己的店铺配置「搬」到他人租户下，破坏隔离）。

**建议**：`unset($data['tenant_id'])`；创建时显式 `$record->tenant_id = Filament::getTenant()->getKey()`；用 `updateOrCreate(['tenant_id' => $tenantId], Arr::except($data, ['tenant_id']))`。

### MALL-05 ｜ 高 ｜ 信息泄露 / 功能错误
**商城公共接口不按租户过滤，泄露全平台自提点/退货地址的联系人与详细地址**

- **位置**：`app/Http/Controllers/Mall/PickupPointController.php:18-25`、`ReturnAddressController.php:18-26`、`routes/apis/mall.php:121`、`:126`、`PickupPointResource.php`、`ReturnAddressResource.php`

```php
$list = PickupPoint::ofEnabled()->bySort()->get();      // 无 tenant 过滤
$list = ReturnAddress::ofEnabled()->orderBy('is_default','desc')->bySort()->get();
```

**失败场景**：两个模型都是租户维度，Resource 直接输出 `contact`、`phone`、`full_address`。① 未登录用户一次请求即可拿到**全平台所有仓库/自提点的联系人手机号与详细地址**；② 结算页拉到的自提点多数不属于当前商品租户，而 `CreateOrder::before` 又强制租户一致，用户选了就报「自提点不正确」。

**建议**：接口要求 `tenant_id`（或由商品/店铺上下文推导）并 `->ofTenant($tenantId)`；未提供返回 422，禁止全表返回。

### MALL-06 ｜ 高 ｜ 数据一致性
**Filament 删除订单/批量删除/强制删除绕过 `OrderService::delete`，不释放库存与券**

- **位置**：`app/Filament/Tenant/Clusters/Mall/Resources/Orders/Tables/OrdersTable.php:131-138`

```php
Actions\DeleteAction::make()
    ->visible(fn (Order $record): bool => in_array($record->status, [OrderStatus::Pending, OrderStatus::Canceled], true)),
...
Actions\DeleteBulkAction::make(),
Actions\ForceDeleteBulkAction::make(),
Actions\RestoreBulkAction::make(),
```

**失败场景**：`OrderService::delete()` 会在删除待付款订单前回补库存并释放券；Filament 默认 Action 只执行 `$record->delete()`。后台删掉一笔待付款订单后，**库存与已核销券不会立即归还**，只能指望 30 分钟后 `AutoCloseOrder`（队列丢失/失败即永久泄漏），被删订单之后还可能收到取消通知。更严重的是提供了 `ForceDeleteBulkAction`——永久删除订单，`order_items`、`refunds`、`event_logs` 全部成为孤儿（无外键）。

**建议**：删除改用 `->action(fn (Order $r) => service(OrderService::class)->delete($r, Filament::auth()->user()))`；移除订单的强制删除/恢复（或仅系统账号可用）；或在 `Order::deleting` 统一释放资源（与 `Cancel` 幂等）。

### MALL-07 ｜ 高 ｜ 空指针
**SKU/商品被软删后，订单详情与「代用户退款」直接 500**

- **位置**：`app/Http/Resources/Mall/OrderItemResource.php:36-48`、`app/Filament/Actions/Mall/CreateRefundAction.php:196-206`

```php
$orderable = $this->resource->orderable;
$data = [
    'type' => $orderable->getMorphClass(),
    'name' => $orderable->getOrderableName(),
```

**失败场景**：`Sku`/`Product` 使用 `SoftDeletes`，商家下架删除 SKU 后 `orderable` 返回 `null`。历史订单详情 500、租户「代用户退款」500，售后被阻断（下单后删 SKU 是常见操作）。

**建议**：`formatOrderable()` 用 `?->` 并对 null 回退到明细快照 `orderable_name`/`orderable_type`；`CreateRefundAction` 用 `$item->orderable?->getOrderableName() ?? $item->orderable_name`。

### MALL-24 ｜ 高 ｜ 金额契约
**订单金额访问器返回 `float`，与全项目「金额字符串 + bcmath」约定不一致**

- **位置**：`app/Models/Mall/Order.php:184-195`、`app/Models/Mall/OrderItem.php:58-61`

```php
public function getTotalAmount(): float
{
    return (float) bcadd(bcsub((string) $this->amount, (string) $this->coupon_discount, 2), $this->freight, 2);
}
public function getSubTotalAttribute(): float
{
    return (float) bcmul($this->qty, $this->price, 2);
}
```

**失败场景**：`OrderAmountTest` 两条用例期望 `'13.00'`/`'12.60'`，实际返回 `13.0`/`12.6`，测试长期失败。API 输出时 `13.0` 会被 JSON 序列化为 `13`（或 `13.0` 视配置），客户端按两位小数解析会失败；同时 `PaymentService`、`DeliveryService`、`OrderPaymentAction` 混用 float 与 string，容易在比较/四舍五入时产生 0.01 误差。

**建议**：全链路统一返回两位小数字符串（`number_format` 或 `bcadd(...,2)`），`float` 只用于展示层；同步更新 phpdoc 与测试。

---

## 四、中优先级问题

| 编号 | 问题 | 位置 | 说明与建议 |
|---|---|---|---|
| MALL-08 | 用户侧退货退款永远退不到运费 | `RefundController.php:44-60`、`RefundRequest.php:33-48`、`Apply.php:540-545` | `RefundRequest` 没有 `freight_amount`，Controller 未传 `freightAmount`，`ReturnRefund` 的运费恒 0；运营端却有「退运费」输入。补字段或在产品口径上明确并删除分支。 |
| MALL-09 | `checkPermission` 对空用户放行 | `AuthorizesModelAccess.php:20-31` | fail-open（详见基础设施 INF-05）。另外 `RefundController:40/136/154/178` 直接 `$order->user->isNot()` 会在 user 为空时 500。 |
| MALL-10 | `refunds.no` 无唯一索引却是路由键 | `create_refunds_table.php:20-21`、`Refund.php:57-60` | 并发/同微秒可能重复；`GET /mall/refunds/{refund}` 命中不确定。补 `unique`、清理历史重复。 |
| MALL-11 | `OpenStoreAction` 重复 `visible()` 覆盖权限校验 | `app/Filament/Actions/Tenant/OpenStoreAction.php:28`、`:35` | 第二次 `visible()` 覆盖第一次，`TenantPolicy::openStore` 失效。合并为一个闭包。 |
| MALL-12 | 订单/退款 Tab 徽章统计绕过租户作用域 | `ManageOrders.php:26`、`ManageRefunds.php:26` | 直接 `Order::tap(...)->count()`，显示全平台数量且泄露其他租户规模。加 `ofTenant(Filament::getTenant())`。 |
| MALL-13 | 支付事务内直接发通知/事件，失败会回滚「已支付」 | `OrderActions/Pay.php:91-100` | 微信已扣款但 `tenant->notify()` 或 `GrantIdentityOnOrderPaid` 抛错 → 事务回滚，订单仍待付款。移到 `DB::afterCommit()` 或 try/catch。 |
| MALL-14 | `skus.sale` 销量从未累加 | `app/Models/Mall/Sku.php:147-157` | 全仓无 `increment('sale')`，排序、推荐热度、销量展示全部失真。支付成功累加、退款完成回退。 |
| MALL-15 | 推荐个性化按 `Product` 查询，实际是 `Sku` | `RecommendationService.php:194-210` | `orderable_type` 落库为 `App\Models\Mall\Sku`，查询永远 0 行，个性化分恒 0。改为 `Sku::class` 并通过 `orderable.product` 取分类。 |
| MALL-16 | 批量审核/批量发起退款无整体事务 | `ApproveRefundBulkAction.php:65-77`、`ConfirmRefundBulkAction.php:60-72` | 第 3 条失败时前 2 条已成功，界面只提示失败。逐条收集成功/失败清单汇总提示。 |
| MALL-17 | 购物车 SKU 被删后结算预览/下单 500 | `CartController.php:112-118`、`:237` | `$item->sku` 为 null 时 `getOrderablePrice()`/`OrderItemDto::make(null)` 报错。先过滤失效行并提示，`?->` 兜底。 |
| MALL-18 | `getOrCreateCart` 先查后建，并发首访唯一键冲突 | `CartService.php:166-179` | 改为 `firstOrCreate` 并捕获唯一键冲突后回查。 |
| MALL-19 | 退款单价先四舍五入到分再乘数量，尾差永久不可退 | `RefundActions/Apply.php:444-456`、`:501-508` | 100/3 → 单价 33.33，退满 3 件只退 99.99，剩 0.01 无法再申请。最后一笔按剩余可退金额兜底。 |
| MALL-25 | 运费规则匹配在「测试 stdClass 商品」下被跳过 | `DeliveryService.php:183-194` | `resolveProduct()` 只接受 `Product` 实例，注释却声称兼容 stdClass 测试项；`createItems()` 造的 `product` 是 stdClass → 重量恒 0。补 `hasAttribute` 分支或删除该兼容声明（`DeliveryServiceTest > weight_type_exceeds_first_weight` 即因此失败）。 |

---

## 五、低优先级问题

| 编号 | 问题 | 位置 | 说明 |
|---|---|---|---|
| MALL-20 | 发货未校验快递单号长度与快递公司存在性 | `OrderShipAction.php:106-108`、`Deliver.php:83-87` | `express_no` 列 32，粘贴长单号直接 500；`express_id` 不校验。补 `maxLength(32)` 与 `exists`。 |
| MALL-21 | 下单未校验自提点与商品关联 | `CreateOrder.php:91-98`、`Product.php:219-222` | 只校验租户与启用，未校验 `pickup_point_product`，可对任意本租户自提点下单。 |
| MALL-22 | `OrderResult` DTO 无引用且金额口径错误 | `DTOs/OrderResult.php:51-90` | `getAmount()` 只累加 `orders.amount`，忽略运费/券。删除或改走 `getTotalAmount()`。 |
| MALL-23 | 删除订单三方口径冲突 | `OrderPolicy.php:50-57`、`Delete.php:51-53`、`ViewOrder.php:54-55` | 策略允许 `canceled/completed`，服务允许 `pending/canceled`，按钮只对 `pending/canceled` 可见。统一到一处。 |
| MALL-26 | 订单号/退款号共用生成逻辑，前缀无区分 | `Traits/AutoCreateOrderNo.php`、`Refund.php` | 退款单号与订单号同空间，排查/对账易混。建议独立前缀。 |

---

## 六、与测试结果的对应

| 失败用例 | 根因 |
|---|---|
| `DeliveryServiceTest` 5 条（区域/包邮/重量） | 3 条区域+包邮：`DeliveryRuleFactory` 未设置 `status`（默认为 false，被 `where('status', true)` 过滤）；1 条重量：MALL-25（stdClass 商品被丢弃） |
| `OrderAmountTest` 2 条 | MALL-24（float vs 两位小数字符串） |
| `TenantMallConfigurePageTest` 2 条 | 页面 `form` 组件与 Filament 5 测试 API 不一致（见测试报告），同时 MALL-04 说明该页面缺少服务端字段白名单 |

---

## 七、建议修复顺序

1. **P0（本周）**：MALL-01（退款断链）、MALL-02（超发/卡单）、MALL-03（跨租户核销）、MALL-04（跨租户改主键）、MALL-06（删单不释放资源 + 强制删除）。
2. **P1（两周）**：MALL-05（PII）、MALL-07（软删 SKU 500）、MALL-10 / MALL-14 / MALL-24、MALL-13。
3. **P2**：MALL-08、MALL-09、MALL-11 ~ MALL-19、MALL-25。
4. **P3**：MALL-20 ~ MALL-23、MALL-26。
