# 优惠券系统实施日志

> 实施依据：[coupon-system.md](coupon-system.md)。本文件记录实施进度、复核发现与修正，实时更新。
> 图例：☐ 待做 / ✅ 已完成 / 🔧 已修正 / ⚠️ 发现问题 / ➡️ 移交后续子任务

---

## 一、进度总览

| 子任务 | 状态 | 说明 |
|--------|------|------|
| **C1 金额口径** | ✅ 完成（本次复查通过） | orders 加 `coupon_discount`、`getTotalAmount()` 新口径、资源/后台展示同步 |
| **C2 核销服务** | ✅ 完成（含测试用例） | CouponService 核销四方法 + 发放加锁 + 过期清理命令 + 死代码清理 + code 移除 + 26 个新测试 |
| **C3 链路接入** | ✅ 完成 | 下单/预览/立购接入券参数、可用券接口、请求校验、预览资源字段改造（破坏性） |
| **C4 返还与闭环** | ✅ 完成 | 取消/全额退款返还、退款分摊、运费上限、超退兜底、抽奖券奖品闭环 |
| **存量测试修复** | ✅ 完成（本次顺带） | users.tenant_id 移除引发的夹具/断言失效 + 券接口租户过滤 |

---

## 二、C1 金额口径 — 实施明细（✅）

| 项 | 文件 | 状态 | 复核结论 |
|----|------|------|----------|
| orders 迁移加 `coupon_discount` | `database/migrations/0003_02_00_000001_create_orders_table.php` | ✅ | decimal(12) unsigned default 0，符合方案 3.1 |
| `Order::getTotalAmount()` | `app/Models/Mall/Order.php:197` | ✅ | `bcadd(bcsub(amount, coupon_discount, 2), freight, 2)`，与方案一致 |
| `coupon_discount` cast | `app/Models/Mall/Order.php:57` | ✅ | `decimal:2` |
| `coupons()` 关联 | `app/Models/Mall/Order.php:207` | ✅ | withPivot 含 `coupon_user_id` / `discount_amount` |
| API 资源字段 | `app/Http/Resources/Mall/OrderResource.php` | ✅ | `coupon_discount` 已加 |
| 后台展示（Tenant/Backend 双面板） | `OrdersTable` / `OrderInfolist` | ✅ | 仅 `coupon_discount > 0` 显示，bccomp 判定 |

---

## 三、C2 核销服务 — 实施明细（✅）

### 3.1 CouponService（`app/Services/Campaign/CouponService.php`）

| 方法 | 状态 | 复核结论 |
|------|------|----------|
| `calculateDiscount(Coupon, string)` | ✅ | bcmath 化；fixed 分支 min(面额, 基数)；percent 走 `capPercentDiscount` |
| `capPercentDiscount()` | ✅ | 先乘后除保留 4 位中间值 + number_format 四舍五入到分，与 `ProductDiscountService::applyPercent` 口径一致 |
| `baseAmountFor()` | ✅ | 一次查询取适用商品 ID 集；空集合 = 全场；仅 `orderable instanceof Sku` 参与匹配；未命中返回 null |
| `previewDiscount()` | ✅ | 走 `validateAndCalculate` 共用路径，返回 `{base_amount, discount}` |
| `applyToOrder()` | ✅ | 校验七步齐备；乐观核销 `whereKey + where('is_used', false)->update()`，affected=0 抛「已被使用」；写 coupon_user / orders.coupon_discount / coupon_order 三处 |
| `releaseFromOrder()` | ✅ | 幂等（无 coupon_order 记录 no-op）；detach + coupon_discount 归零 + is_used 复位 |
| `validateAndCalculate()`（私有） | ✅ | preview/apply 共用路径：归属 → 未使用 → 未过期 → isValid → 租户匹配 → baseAmountFor → 含 clamp 计算 |
| `calculateWithClamp()`（私有） | ✅ | 最低实付 0.01 clamp，bccomp 判定，符合方案 3.3.2 |
| `sendToUser()` 加锁 | ✅ | `Cache::lock("coupon_send_{id}", 10)->block(5, ...)`，`LockTimeoutException` 捕获转业务异常 |
| `doSendToUser()`（锁内执行） | ✅ | 原校验/插入逻辑不变 |

### 3.2 周边交付

| 项 | 文件 | 状态 |
|----|------|------|
| 过期清理命令 | `app/Console/Commands/Campaign/CouponExpireCommand.php` | ✅ chunkById 批量物理删除，仅清「未使用且已过期」 |
| 调度注册 | `routes/console.php` | ✅ `daily() + onOneServer()` |
| `ValidCouponUserRule` | `app/Rules/Campaign/ValidCouponUserRule.php` | ✅ 存在/归属/未使用/未过期四项校验，`nullable` 兼容 |
| 删除 `ValidCouponRule` | — | ✅ 已删（git status D） |
| 删除 `Coupon::canUserUse()` | `app/Models/Campaign/Coupon.php` | ✅ 已删，全库无残留引用 |
| 删除 `coupons.code` | 迁移 / 工厂 / 双面板 Form·Infolist·Table 共 8 处 | ✅ 全部移除，全库无 coupon code 残留引用 |

---

## 四、复查发现与修正（2026-09-11）

### 🔧 已就地修正

| # | 问题 | 级别 | 修正 |
|---|------|------|------|
| 1 | `createOrders()` 券定义被软删时 `$couponUser->coupon` 为 null → 访问 `->tenant_id` 抛 Error（500 而非业务提示） | 🟡 | 加 `!$couponUser->coupon` 判断，null 时同样拒绝整单「优惠券不适用于所选商品」（方案 §四：软删券按不可用处理） |
| 2 | Pint 未过（import 顺序 + phpdoc 分隔） | 🟢 | `vendor/bin/pint` 自动修复 `CouponService` / `OrderService`，无语义变化 |
| 3 | `calculateDiscount()` fixed 分支返回 `(string) $coupon->value` 未归一化——decimal 字段在 SQLite 等驱动下返回数值，`(string) 10.00` → `'10'`，金额字符串丢小数位 | 🟡 | 改为 `number_format((float) $coupon->value, 2, '.', '')`，与 `capPercentDiscount` 归一化口径一致（测试 `test_min_amount_boundary_equal_passes` 暴露） |

### ⚠️ 确认无问题、无需改动的点

| 检查项 | 结论 |
|--------|------|
| `applyToOrder` 乐观核销与 `releaseFromOrder` 的 update 不加 `is_used` 条件 | 释放侧按 coupon_order 记录定位券实例，记录删除前不会误恢复他单占用的券（一单一券，实例被本单占用）；核销侧已带 `is_used = false` 条件防并发 |
| `OrderService::createOrder` 核销位置 | 在订单创建 + 订单项写入之后、地址/事件之前，同事务；`log()` 记录 `coupon_user_id / coupon_discount`，符合方案 3.3.3 注 |
| 跨店拆单透传 | `createOrders` 仅向券所属租户子订单透传；券租户不在拆单集合 → 拒绝整单（含本次修正的软删券场景） |
| 0.01 clamp 与 fixed 内层上限 | 两层独立生效（`calculateDiscount` 保证 ≤ 基数；`calculateWithClamp` 保证实付 ≥ 0.01），与方案 3.3.2 一致 |
| `coupon_user` 无唯一约束 | 方案已明确由锁 + 乐观更新保证，不改表结构 |

### ⚠️ 遗留观察项（不阻塞 C1/C2，转 C3/C4 处理）

| # | 项 | 去向 |
|---|-----|------|
| 1 | `createOrder` 内核销失败的异常类型为 `InvalidArgumentException`，控制器层需转 4xx 响应 | C3（控制器接入时统一） |
| 2 | `previewDiscount` / `applyToOrder` 尚无调用方（预览接口、可用券接口） | C3 |
| 3 | `releaseFromOrder` 尚无调用方（cancel / 全额退款） | C4 |
| 4 | `OrdersRelationManager`（券详情）尚无抵扣金额列 | C4（方案 §3.10） |
| 5 | `previewDiscount()` 内部重复调用 `baseAmountFor()`（校验路径已算过一次，返回时再查一次），可用券列表接口按券循环时会放大查询量 | C3（可用券接口实现时一并优化为单次计算） |

---

## 四A、存量测试修复（2026-09-11，用户确认后执行）

### 背景

`users.tenant_id` 列已移除（commit 5489424d 改为 `user_tenant` 多对多），测试夹具与断言未同步，导致大量存量失败。经确认后按以下口径修复。

| # | 问题 | 修正 | 文件 |
|---|------|------|------|
| 1 | 夹具向已不存在的列写入：`User::factory()->create(['tenant_id' => ...])` | 改为 `User::factory()->create()` + `$user->tenants()->attach($tenant)` | `tests/Feature/Campaign/CampaignApiTest.php`（7 处）、`tests/Feature/Mall/RefundAutoApproveTest.php`（1 处） |
| 2 | 断言 `users.tenant_id` | **决策：注册不自动关联租户**，断言改为仅校验用户创建成功（租户授权由后台 `AuthorizeTenantAction` 负责） | `tests/Feature/Auth/RegisterApiTest.php` |
| 3 | 券接口租户口径错误：`index` 无租户过滤、`show` 跨租户未 404、`mine`/`stats` 未按用户租户过滤；且来源用 `$request->query('tenant_id')` 而非项目约定的 `X-Tenant-Id` | **决策：按测试意图修正控制器**——来源改为 `TenantResolver`（X-Tenant-Id）：`index` 按当前租户过滤；`show`/`claim` 要求券租户 = 当前租户，且已登录用户须持有该租户授权（防伪造租户头）；未带租户头时按用户授权租户集合判定；`mine`/`stats` 按「授权租户 ∩ 请求头租户」过滤 | `app/Http/Controllers/Campaign/CouponController.php` |
| 4 | `test_coupon_list_validates_filter_params` 期望同时返回 `type`/`limit` 两个错误 | **决策：保留 `BaseFormRequest::$stopOnFirstFailure = true` 原有行为**，断言改为仅校验首个字段错误（`type`） | `tests/Feature/Campaign/CampaignApiTest.php` |

### 结果

- `CampaignApiTest` **21/21 通过**（修复前：7 errors + 3 failures）
- `CouponServiceTest` **43/43 通过**

### 仍有失败（与本次改动无关，属其他存量腐化，另开任务）

| 家族 | 症状 | 影响用例数 |
|------|------|-----------|
| 抽奖 | `lottery_draws` 表缺 `updated_at`（模型/工厂与迁移不一致） | LotteryServiceTest 10 |
| Sku 夹具 | `skus` 表无 `tenant_id` 列，工厂仍传入 | RefundAutoApproveTest 4 |
| 注册 | 测试数据未带 `password_confirmation`，与 `confirmed` 校验规则冲突；期望 400 实为 422 | RegisterApiTest 5 |
| 收藏/订单过期/运费 | 另有独立失败（ProductFavoriteApiTest 4、OrderExpirationTest 2、DeliveryServiceTest 5） | 11 |
| 环境 | 图形验证码依赖 GD/字体文件；全量跑撞 128M 内存上限 | LoginApiTest、全量运行 |

---

## 四B、C3 链路接入 — 实施明细（✅）

| 项 | 文件 | 改动 |
|----|------|------|
| 订单项轻量构造 | `app/Services/Mall/DTOs/OrderItemDto.php` | 新增 `forPreview()`（`check: false` 跳过库存/可售校验），供结算预览与可用券预估使用；单价仍由调用方传入折后价，与下单同源 |
| 服务防御 | `app/Services/Campaign/CouponService.php` | `validateAndCalculate()` 增加空订单项守卫 → 抛「优惠券不适用于所选商品」（避免 `$items->first()` 空指针 500） |
| 请求校验 | `CheckoutPreviewRequest` / `OrderFromCartRequest` / `OrderPreviewRequest` / `OrderRequest` | 新增 `coupon_user_id`（`nullable`+`numeric`+`ValidCouponUserRule`）与中文错误消息 |
| 购物车预览 | `CartController::preview()` | 按券所属租户筛选商品项 → `previewDiscount()`；返回 `goods_amount`/`coupon_discount`/`freight`/`payable_amount`；券不可用返回 400 与原因 |
| 购物车下单 | `CartController::createFromCart()` | 透传 `couponUserId` 至 `createOrders()` |
| 立即购买预览 | `OrderController::preview()` | 券租户与商品租户一致时才构造抵扣项，否则交由服务口径拒绝 |
| 立即购买下单 | `OrderController::create()` | 透传 `couponUserId` |
| **破坏性变更** | `CheckoutResource` / `OrderPreviewResource` | `total_amount` → `goods_amount`，新增 `coupon_discount`（口径：`payable = goods + freight − coupon_discount`） |
| 可用券接口 | `CouponController::available()` + `CouponAvailableRequest` + `CouponAvailableResource` + `routes/apis/campaign.php` | 新增 `GET /campaign/coupons/available`，入参 `items[{sku_id,qty}]`，按租户分组返回可用券与 `discount_preview`/`base_amount`，不可用券带 `inapplicable_reason` |
| 文档同步 | `docs/apis/mall.md` / `docs/apis/campaign.md` | 预览字段对照表与破坏性变更标注；新增可用券接口章节（campaign 文档章节顺延重编号 6→7…15→16）；订单侧 `total_amount` 口径说明与 `coupon_discount` 字段 |

### 测试（`tests/Feature/Mall/CouponCheckoutTest.php`，新增 11 用例，全绿）

购物车下单带券（三表写入断言）、立即购买带券、身份折扣叠加（券基数按折后单价）、券不可用预览报错、跨店拆单仅抵扣所属租户子订单、券租户不在拆单集合拒绝整单、同券二次使用被拒、可用券接口（分组/可用与不可用/排除已用与过期/入参校验/类型标签）。

另同步更新既有断言：`ProductDiscountTest` 预览 `total_amount` → `goods_amount`（破坏性变更所致）。

### ⚠️ C3 期间遗留

| # | 项 | 说明 |
|---|-----|------|
| 1 | ~~`available` 接口的券实例过滤未按「用户授权租户」收窄~~ | ✅ 已定口径并统一（见下） |
| 2 | 券抵扣的 `coupon_discount` 尚未在订单列表/详情的字段表中单列 | JSON 示例已加，字段表以文档正文为准 |

### 🔒 券接口租户口径（2026-09-11 定稿）

**统一规则**：所有「我的券」类接口按**本次请求生效租户集合**过滤——

| 场景 | 生效租户集合 |
|------|-------------|
| 带 `X-Tenant-Id`（须为用户已授权租户） | 该租户 |
| 未带 `X-Tenant-Id` | 用户全部授权租户 |

| 接口 | 口径 |
|------|------|
| `GET /campaign/coupons/my`、`/stats` | 按生效租户集合过滤（`effectiveTenantIds`） |
| `GET /campaign/coupons/available` | 同上；集合外的券**不返回**（而非置灰），集合内不可用券才带 `inapplicable_reason` |
| `GET /campaign/coupons/{id}`、`POST .../claim` | 券租户须等于请求头租户；带伪造租户头（非用户授权）一律 404 |

**测试影响**：券接口测试的用户必须先建立租户授权（`$user->tenants()->attach($tenant)`），否则生效租户集合为空、接口返回空集。

> ⚠️ 顺带观察（非本任务引入）：`TenantResolver` 把解析结果缓存在 `Context` 中，框架**不会**在同进程的多个请求之间自动 `flush`。FPM 下每请求新进程无影响；若将来启用 Octane/常驻进程需确认 Context 的请求级清理，否则同一 worker 内会串租户。已在测试中以拆分用例规避。

## 四C、C4 返还与闭环 — 实施明细（✅）

### C4-1 取消 / 全额退款返还

| 位置 | 改动 |
|------|------|
| `OrderService::cancel()` | 订单置为已取消后调用 `CouponService::releaseFromOrder()`（事务内） |
| `RefundService::confirmRefund()` | 抽出 `allItemsRefunded()` 判定；全部订单项退完后释放券并更新订单状态。**部分退款不返还**（券作用于整单基数） |

### C4-2 退款金额分摊（资损防护）

| 位置 | 改动 |
|------|------|
| `RefundService::couponShares()` | 新增：分摊比例 = `coupon_discount ÷ amount`，各项四舍五入到分后，**累积尾差归位到金额最大的订单项**，保证 Σ分摊 = 券抵扣 |
| `RefundService::netItemAmounts()` / `netUnitPrice()` | 新增：订单项分摊后可退金额与可退单价（2 位） |
| `RefundService::refundedQtyOf()` | 新增：已占用退款数量（进行中 + 已完成；已取消/已拒绝/失败不计） |
| `calculateRefundAmount()` | 商品金额改为「分摊后可退金额」口径；本笔按剩余可退金额兜住单价尾差；运费改由 `calculateRefundFreight()` 计算 |
| `calculateRefundFreight()` | **连带修复运费重复退还**：上限为「订单运费 − Σ已退运费」，未发货订单首笔退全额、后续笔为 0 |
| `assertRefundWithinPaid()` | 新增兜底校验：`Σ(该订单全部有效退款 total) + 本笔 ≤ 订单实付`，超出则拒绝 |

> 未用券订单分摊为空数组、净额 = 原小计，行为与现状完全一致（`test_refund_without_coupon_keeps_original_amount` 回归）。

### C4-3 抽奖券奖品闭环

| 位置 | 改动 |
|------|------|
| `LotteryService::fulfillPrize()` | Coupon 类型改走 `fulfillCouponPrize()`（不再落入「仅实物奖品需要兑奖」分支） |
| `LotteryService::fulfillCouponPrize()` | 新增：读 `prize_detail.coupon_id` → 查券 → `CouponService::sendToUser()` 发放 → 置 `Fulfilled`；未配置/券不存在/券停用等失败**抛异常且保持 Pending**（事务回滚），供人工处理 |
| `app/Models/Campaign/LotteryDraw.php` | **顺带修复存量 bug**：`lottery_draws` 表为 append-only（仅 `created_at`），模型却用默认 `timestamps()`，抽奖写入全部报 `no column named updated_at`；加 `const UPDATED_AT = null` 对齐表结构（同时解锁 10 个存量失败用例） |

### 测试

- `tests/Feature/Mall/CouponRefundTest.php`（新增 11 用例）：取消返还、全额退款返还、部分退款不返还、释放幂等、按比例分摊（60/40 → 48/32）、尾差归位（33.33/33.33/33.34 + 券10 → Σ退 90）、无券订单行为不变、运费跨多笔退款只退一次、超退兜底拒绝、已完成退款的订单项不可重复申请、券抵扣后全额退款不超退
- `LotteryServiceTest` 新增 3 个券奖品用例（发放成功、未配置券、券停用保持 Pending），原「非实物不可兑」用例改用 `Balance` 类型

### ⚠️ C4 期间发现的问题

| # | 问题 | 级别 | 状态 |
|---|------|------|------|
| 1 | `OrderItem::getMaxRefundCounts()` 只统计**进行中**状态（不含 `Completed`），已退完的订单项数量可再次申请退款 | 🟡 | ✅ **已修**（用户确认）：新增 `RefundStatus::effectiveCases()`（进行中 + 已完成），`getMaxRefundCounts()` / `getShippableQtyAttribute()` / `RefundService` 金额口径统一复用，消除三处重复。详见下方 |
| 2 | 全额退款释放券后 `orders.coupon_discount` 归零、`total_amount` 回到未抵扣口径，开票金额会高于实付 | 🟡 | ✅ **已修**（用户确认）：拆分为两条释放路径——取消归零、全额退款保留快照。详见下方 |
| 3 | `LotteryServiceTest::test_get_available_draws_returns_max_when_no_draws` 期望 5 实得 3 | 🟢 | ✅ **已修**（测试过时）：免费模式下实际可抽次数 = min(总次数剩余, 今日免费次数剩余)，与 `resolveCostType()` 准入一致；工厂默认 `free_draws_per_day = 3` 使旧断言失效。已改写该用例并新增每日免费次数约束用例 |

#### 退款数量口径统一（2026-09-11 修复 #1）

新增 `RefundStatus::effectiveCases()`：`Pending / WaitingReturn / Shipping / Received / Processing / Completed`（即「进行中 + 已完成」），已拒绝 / 已取消 / 失败视为未发生。

| 使用处 | 改动 |
|--------|------|
| `OrderItem::getMaxRefundCounts()` | 由「仅进行中」改为 `effectiveCases()`；并补 `max(0, …)` 防御 |
| `OrderItem::getShippableQtyAttribute()` | 原有的 `whereNotIn(Rejected/Cancelled/Failed)` 等价改写为 `effectiveCases()`，两方法口径一致 |
| `RefundService`（3 处金额口径） | 删除私有 `countedRefundStatuses()`，统一改用 `effectiveCases()` |

**连带影响**：`app/Filament/Actions/Mall/OrderShipAction.php` 以 `getMaxRefundCounts() < qty` 判定「不可发货」——修复后**已完成退款的商品不再出现在可发货列表**（此前会被列出，属同一 bug 的另一处表现），与 `getShippableQtyAttribute` 已表达的口径一致。

#### 释放的两条路径（2026-09-11 修复 #2）

| 场景 | 方法 | `orders.coupon_discount` | 理由 |
|------|------|--------------------------|------|
| 未支付取消 | `releaseFromOrder()` | **归零** | 订单从未支付，券从未真正抵扣 |
| 全额退款完成 | `releaseFromRefundedOrder()` | **保留快照** | 钱已实际收付、退款已按实付口径分摊，归零会让订单历史实付金额凭空变大（开票 `InvoiceService` 按 `total_amount` 累加、后台展示均受影响） |

两个方法共用私有 `doRelease(Order $order, bool $resetDiscount)`，幂等语义不变（无 `coupon_order` 记录即 no-op）。

> ⚠️ **仍然存在的独立问题（非本次引入，建议单独立项）**：`InvoiceService::validateOrders()` **不校验订单状态与退款情况**，已取消/已全额退款的订单仍可申请开票，且金额按 live `total_amount` 计算。本次修复避免了「券订单在退款后被追补开票金额」，但「已退款订单能否开票」这一根本问题需在 Finance 模块单独处理（例如排除已退款订单或按净额开票）。

---

## 八、开票状态校验（✅ 已完成，用户 2026-09-11 要求）

**背景**：`InvoiceService::validateOrders()` 原先只校验存在性 / 租户 / 归属 / 重复开票，**不校验订单状态与退款情况**——已取消、已全额退款的订单仍可开票，且金额按 live `total_amount` 计算。

| 位置 | 改动 |
|------|------|
| `InvoiceService::validateOrders()` | 新增：订单为 `pending`（未支付）或 `canceled`（已取消）→ 拒绝「订单未支付或已取消，不可开票」；订单存在**有效退款**（`RefundStatus::effectiveCases()`，即进行中 + 已完成）→ 拒绝「订单已申请退款，不可开票」 |
| `InvoiceController::invoicableOrders()` | 列表口径同步：新增 `whereDoesntHave('refunds', effectiveCases)`（原已排除 `pending` / `canceled`），保证列表与服务校验完全一致 |

**口径要点**：

- 已取消/已拒绝/失败的退款**不**阻断开票 → 退款被驳回后订单恢复可开票
- 已全额退款订单状态为「已签收/已完成」，仅靠状态无法识别，须由退款记录拦截
- 券订单开票金额为实付口径（`amount + freight − coupon_discount`），全额退款保留抵扣快照后该口径不再被改写

**测试**：新增 `tests/Feature/Finance/InvoiceServiceTest.php`（8 用例）——已支付无退款可开票、已取消/未支付被拒、进行中退款被拒、全额退款被拒、退款被拒绝后可开票、券订单开票金额为实付（100 + 10 − 20 = 90）、可开票列表排除退款项与已取消项。**8/8 通过**（开票申请会触发 Redis 广播通知，测试中以 `Notification::fake()` 拦截）。

> ⚠️ 存量失败（非本次引入）：`tests/Feature/Finance/WithdrawServiceTest.php` 全部失败，报 `Unknown named parameter $tenantId`（`WithdrawService` 签名已变、测试未同步），属「多租户重构后测试腐化」家族，未处理。

## 五、验证记录

- `vendor/bin/phpunit tests/Feature/Campaign/CouponServiceTest.php` → **43 tests, 76 assertions, OK**
- `vendor/bin/phpunit tests/Feature/Campaign/CampaignApiTest.php` → **21 tests, 35 assertions, OK**
- `vendor/bin/phpunit tests/Feature/Campaign/LotteryServiceTest.php` → **17 tests, 34 assertions, OK**
- `vendor/bin/phpunit tests/Feature/Mall/CouponCheckoutTest.php` → **15 tests, 68 assertions, OK**
- `vendor/bin/phpunit tests/Feature/Mall/CouponRefundTest.php` → **11 tests, 31 assertions, OK**
- `vendor/bin/phpunit tests/Feature/Mall/ProductDiscountTest.php` → **11 tests, 29 assertions, OK**
- `vendor/bin/phpunit tests/Feature/Finance/InvoiceServiceTest.php` → **8 tests, 16 assertions, OK**
- `vendor/bin/pint`（改动文件）→ 通过
- 其余存量失败已在「四A」「八」中分类记录，与本任务改动无关（已用 stash 在 HEAD 上复现）

---

## 六、下一步（按方案 §五任务拆分）

1. ~~**C2 收尾**：补写 C2 测试用例~~ ✅ 已完成（26 个新用例）
2. ~~**C3 链路接入**：控制器 + 请求校验 + 预览资源字段改名 + `GET /campaign/coupons/available`~~ ✅ 已完成（14 个新用例）
3. ~~**C4 返还与闭环**：cancel / confirmRefund 释放、退款分摊、运费退还上限、抽奖 Coupon 奖品~~ ✅ 已完成（13 个新用例）

> **四个子任务已全部完成**。遗留待确认项见「四A / 四C 期间发现的问题」，均为存量问题或口径决策，不影响本次交付的优惠券闭环。

---

## 七、订单项级券分摊快照（✅ 已完成）

**诉求**（用户 2026-09-11 提出）：`orders` 已记录整单 `coupon_discount`，`order_items` 也应记录该订单项分摊到的抵扣金额；分摊比例按「订单项小计 ÷ 订单商品总额」计算；后台逐项展示。

### 实施明细

| 位置 | 改动 |
|------|------|
| `database/migrations/0003_02_00_000001_create_orders_table.php` | `order_items` 新增 `coupon_discount` decimal(12) unsigned default 0（改源文件 + 全量重建） |
| `app/Models/Mall/OrderItem.php` | cast `coupon_discount => decimal:2` |
| `CouponService::apportionDiscount()` | **新增**：分摊算法从 `RefundService` 上移，比例 = 项小计 ÷ 小计合计，尾差归位金额最大项，保证 Σ分摊 = 抵扣金额 |
| `CouponService::applyToOrder()` | 核销时按上述算法把分摊额写入各订单项（与 `orders.coupon_discount`、`coupon_order` 同事务落库） |
| `RefundService::netItemAmounts()` | 改为**读快照**（`order_items.coupon_discount`），删除重复的 `couponShares()` 重算逻辑 |
| `OrderItemResource` | 新增 `coupon_discount` 字段 |
| Tenant / Backend `ItemRelationManager` | 新增「券抵扣」列（仅 > 0 时显示） |

**设计要点**：分摊算法只保留一份实现（`CouponService::apportionDiscount()`），下单写入与退款读取共用同一口径，避免两处算法漂移；退款不再重算，展示与可退金额天然一致。

### 测试

- `CouponCheckoutTest` 新增 `test_cart_checkout_writes_item_level_coupon_share`（60/40 订单券 20 → 逐项 12/8）并在单商品用例中断言该项拿到全额分摊 → **15 用例全绿**
- `CouponRefundTest::applyCoupon()` 夹具改为调用真实 `apportionDiscount()` 写快照（与下单同路径）→ **11 用例全绿**
- 尾差归位仍由 `test_coupon_share_rounding_tail_lands_on_largest_item` 覆盖（33.33/33.33/33.34 + 券 10 → Σ退 90）

> **方案文档相应修订**：§3.7.3 原「退款时按比例分摊」改为「读取下单落库的分摊快照」；§3.1 / §3.3.2 / §八 同步补充 `order_items.coupon_discount`。
