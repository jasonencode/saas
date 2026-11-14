# Finance 模块 检查报告

- **检查日期**：2026-11-14
- **检查范围**：`app/Models/Finance/**`、`app/Services/Finance/**`、`app/Http/{Controllers,Requests,Resources}/Finance/**`、`app/Policies/Finance/**`、`app/Enums/Finance/**`、`routes/apis/finance.php`、Finance 相关迁移、`app/Filament/**` 中 Finance 资源/表单/表格/动作、`tests/Feature/Finance/**`
- **检查方式**：关键链路走读（支付/退款/充值/提现/发票/结算）+ 迁移与调用方交叉核对
- **结论**：资金主链路的行锁、条件更新与微信退款对账已落地，旧报告部分问题修复；但**租户 `tenant_id` 恒 null 走错商户、可退额度忽略软删除且退款单可删、充值完成链路无锁/无幂等、支付单可绑定他人业务主体**四条资损级缺口仍在。本轮另发现「开具发票必查不存在的列」与「租户财务看板跨租户聚合」两个新问题。

**统计：严重 3 / 高 7 / 中 9 / 低 1（共 20 条）。**

---

## 一、模块概览

| 子域 | 现状 | 主要风险 |
|---|---|---|
| 支付单 | 支付单行锁 + 幂等出口；微信 v3 已接入回调/退款/对账 | 租户为空导致选错商户；可绑定他人业务主体 |
| 退款 | `PaymentRefund` 状态机 + 条件更新抢占；极速退款自动审核 | 额度统计忽略软删；后台可删；回调不校验金额 |
| 充值 | 订单 → 支付单 → 回调入账 | 充值单无锁/无条件更新；同单多支付单可重复入账或不到账 |
| 提现 | 冻结/审核/打款锁内校验 | 提现单无 `tenant_id`；可批量/强制删除导致冻结资金卡死 |
| 发票 | 申请/开具/驳回 | `issue()` 必因 `users.tenant_id` 报错；可编辑改金额/状态 |
| 结算 | Plan/Task/Voucher | 全链路无调用方，后台建单不派发 Job |

---

## 二、严重问题

### FIN-01 ｜ 严重 ｜ 租户 / 支付

**充值单/支付单 `tenant_id` 恒为 null，微信支付选到任意租户商户号**

- **位置**：`app/Http/Controllers/Finance/RechargeController.php:46`、`PaymentController.php:60`、`app/Models/Traits/BelongsToTenant.php:31-38`
- **代码要点**：`tenantId: Auth::user()?->tenant_id`，而 `users` 表没有 `tenant_id` 列（租户关系在 `user_tenant`）；`tenant()` 宏可空，`ofTenant(null)` 不过滤。
- **失败场景**：充值单/支付单 `tenant_id = NULL` → `WechatPayment::ofTenant(null)->first()` 取到随机租户商户；下单与回调用不同商户，验签失败、回调永远 FAIL，充值不到账；退款也可能打错商户。
- **修复建议**：`ofTenant(null)` 显式拒绝；Finance 统一用 `TenantResolver::current()` 并校验用户归属；无租户上下文禁止创建微信支付单。

### FIN-03 ｜ 严重 ｜ 资金 / 可退额度

**可退额度忽略软删除 + 后台可删退款单，可重复退款**

- **位置**：`app/Services/Finance/PaymentRefundService.php:51-58`、`app/Filament/Tables/Finance/RefundsTable.php:82-88`、`app/Models/Finance/PaymentRefund.php:22-24`
- **失败场景**：1000 元全额退款完成 → 财务软删该退款单 → `refundableAmount()` 恢复 1000 → 可再次退款；审计链同时被抹掉。
- **修复建议**：额度统计加 `withTrashed()`；资金单据移除删除，改「作废」；库层补汇总约束。

### FIN-04 ｜ 严重 ｜ 充值 / 并发

**充值完成链路无行锁/无条件更新，同单两笔支付可重复入账或钱到账不入账**

- **位置**：`app/Services/Finance/RechargeService.php:71-116`（`complete`）、`:128-149`（`markPaid`）、`database/migrations/0004_00_00_000001_create_payments_table.php:21`
- **失败场景**：同一充值单建两张支付单：① 并发回调都读到 `Pending`，都入账，余额翻倍；② 串行回调第二张时 `markPaid` 抛「状态不正确」，整个回滚，渠道已收款但永不到账、无限重试。
- **修复建议**：条件更新（`where status = Pending` 判影响行数）；`complete()` 事务内 `lockForUpdate()`；`user_account_logs(source_type, source_id, type)` 唯一；支付单建单前判重。

---

## 三、高优先级问题

### FIN-02 ｜ 高 ｜ 越权

**创建支付单不校验 `paymentable` 归属**

- **位置**：`app/Services/Finance/PaymentableResolver.php:55`、`PaymentController.php:46-74`
- **失败场景**：任意登录用户可把他人订单/充值单挂到自己的支付单下，以受害者租户商户号下单，并推进他人订单状态；`show/refund/pay` 已有权限校验，唯独创建入口没有。
- **修复建议**：`resolve()` 增加 `user_id/tenant_id` 约束，控制器校验 `paymentable->user_id === Auth::id()`。

### FIN-24 ｜ 高 ｜ 功能必现失败

**「开具发票」必查不存在的 `users.tenant_id`**

- **位置**：`app/Models/Finance/Invoice.php:36-50`
- **代码要点**：`creating` 钩子执行 `User::whereKey($invoice->user_id)->value('tenant_id')`，`users` 表无此列。
- **失败场景**：`InvoiceService::issue()` 创建发票时不带 `tenant_id` → SQLSTATE 42703，后台开票永远失败；现有发票测试只覆盖 `createApplication`。
- **修复建议**：删除钩子，在 `issue()` 显式写 `tenant_id = application->tenant_id`（或从订单推导）。

### FIN-25 ｜ 高 ｜ 租户数据泄露

**租户端财务看板使用全局聚合**

- **位置**：`app/Filament/Tenant/Clusters/Finance/Widgets/FinanceStatsWidget.php:29-42`
- **失败场景**：任何租户管理员打开财务看板，看到的是全平台余额、冻结额、支付总额、退款数。
- **修复建议**：`PaymentOrder/PaymentRefund/Refund` 加 `tenant_id` 过滤；`UserAccount` 无租户列，需 `whereHas('tenants')`。

### FIN-26 ｜ 高 ｜ 资金 / 审计

**提现单可批量/强制删除，冻结资金永久卡死**

- **位置**：`app/Filament/Backend/Clusters/Finance/Resources/WithdrawOrders/Tables/WithdrawOrdersTable.php:70-76`、`app/Policies/Finance/WithdrawOrderPolicy.php:41-51`
- **失败场景**：删除 Pending/Approved 提现单后 `frozen_balance` 无任何解冻路径，用户资金永久不可用；强删连审计记录一起消失。
- **修复建议**：移除删除/强删/恢复，或仅终态允许删除；作废必须先 `cancel/review(reject)` 解冻。

### FIN-05 ｜ 高 ｜ 支付密码

**6 位支付密码无失败计数，锁粒度按支付单 ID 可绕过**

- **位置**：`routes/apis/finance.php:47-49`、`app/Http/Middleware/EnsureSingleRequest.php:51`、`app/Services/Finance/UserAccountService.php:221-228`
- **失败场景**：锁 key 为 `payment_pay_{paymentId}`，按单不按人；无失败计数/锁定，批量建单即可放大猜解次数。
- **修复建议**：按 `user_id` 累计失败并锁定/退避；支付/提现挂按用户限流器。

### FIN-06 ｜ 高 ｜ 租户 / 对账

**提现单无 `tenant_id`，租户端无充值/提现视图**

- **位置**：`database/migrations/0001_01_00_000001_create_user_accounts_table.php:123-177`、`app/Models/Finance/WithdrawOrder.php:19-23`
- **失败场景**：提现/充值无法按租户统计与对账；租户端只有支付/退款/账户/计划/发票/凭据。
- **修复建议**：`withdraw_orders` 加 `tenant_id` + `BelongsToTenant`，创建时写入；补齐租户端只读列表。

### FIN-13 ｜ 高 ｜ 校验 / 500

**发票申请不带 `order_ids` 必 500**

- **位置**：`app/Http/Requests/User/InvoiceApplicationRequest.php:17-21`、`app/Http/Controllers/User/InvoiceController.php:100-109`、`InvoiceService.php:46`
- **失败场景**：`order_ids` 可为空 → `value('tenant_id')` 返回 null → 传入 `int $tenantId` 抛 TypeError。
- **修复建议**：`order_ids` 改 `required|array|min:1`，或补齐按金额开票分支。

---

## 四、中优先级问题

### FIN-12 ｜ 中 ｜ 回调 / 对账

**回调不校验金额、渠道流水号未落库**

- **位置**：`PaymentController.php:264-272`、`app/Services/Finance/PaymentService.php:87`、`create_payments_table.php:13-49`
- **失败场景**：金额被改写后仍按新金额判定已付；对账需跨表拼 `event_logs` 才能拿到渠道单号；余额支付还会回写 `payment_orders.amount`。
- **修复建议**：回调比对分金额，不一致告警并退款；支付单增加 `transaction_id/channel` 并落库；金额快照化。

### FIN-11 ｜ 中 ｜ 微信配置

**商户配置选取两套口径；停用配置无法退款、支付 500**

- **位置**：`PaymentController.php:189,233`、`PaymentRefundService.php:474-476`、`WechatPaymentService.php:227-233`
- **失败场景**：`first()` 可能取到停用配置 → `initPayment()` 抛异常且 `payWechat` 无捕获 → 500；退款注释说「停用仍可退款」，实际强制 `isEnabled()`。
- **修复建议**：抽 `activeForTenant()` 统一选取；退款允许使用停用配置；通道异常转业务错误。

### FIN-14 ｜ 中 ｜ 并发

**发票重复开具：无唯一约束、无锁、无条件更新**

- **位置**：`InvoiceService.php:202-231`、`create_invoices_tables.php:100-102`
- **修复建议**：`issue()` 用 `where status = Pending` 抢占；`invoices.invoice_application_id` 唯一；订单侧占用唯一。

### FIN-15 ｜ 中 ｜ 级联删除

**强制删除计划级联删除结算凭据**

- **位置**：两侧 `PlansTable.php:52`、`create_settlements_table.php:54-57`（`vouchers.plan_id` cascade）
- **修复建议**：`restrictOnDelete` 或 `nullOnDelete` + 快照；移除 `ForceDeleteBulkAction`。

### FIN-17 ｜ 中 ｜ 账务口径

**提现完成日志口径错乱；`fee` 全链路恒为 0**

- **位置**：`WithdrawService.php:215-222`、`WithdrawController.php:78-87`
- **失败场景**：完成时只减 `frozen_balance`，日志却写成余额 `before + amount = after`，对账无法配平；手续费永不入账。
- **修复建议**：复用冻结资产日志语义（`extra.frozen_before/after`）；`fee` 从配置/请求注入并明确归属。

### FIN-18 ｜ 中 ｜ 业务旁路

**用户可绕过售后直接对已支付订单发起退款**

- **位置**：`PaymentController.php:110-126`、`RefundRequest.php:16-19`
- **失败场景**：已发货/已签收甚至已完成订单可直接申请资金退款，绕过售后状态机与库存回收。
- **修复建议**：只允许「本人 + 存在审核通过的售后单」，或下线该 API。

### FIN-19 ｜ 中 ｜ 后台编辑

**发票申请可被直接改写金额与状态**

- **位置**：`app/Filament/Tables/Finance/InvoiceApplicationsTable.php:53-58`、Backend `InvoiceApplicationForm.php:16-35`
- **失败场景**：已开票申请可改金额/改回 Pending 再开票；租户端仍是空表单编辑入口。
- **修复建议**：移除 `EditAction`；确需修正用受控动作且 `status` 不可编辑。

### FIN-08 ｜ 中 ｜ 结算

**结算子系统三处缺陷：失败日志被回滚 / 脏任务 null / 失败不可重试**

- **位置**：`app/Services/Finance/SettlementService.php:45-67,84-89,110-116`、`app/Jobs/Finance/VoucherAutoRunJob.php:19-24`
- **修复建议**：失败日志独立写入；任务列表 `filter()` 并抛带 task id 的异常；`catch (Throwable)`；增加超时回收。

### FIN-07 ｜ 中 ｜ 死代码

**结算链路无调用方，后台建单也不会执行**

- **位置**：`app/Services/Finance/VoucherService.php:29-71`、`app/Jobs/Finance/VoucherAutoRunJob.php:13`
- **说明**：全仓无 `create()` 调用，`OrderCompleted` 无监听，后台无「执行」动作；`BaseJob` 强制 `redis` 连接。
- **修复建议**：接入 `OrderCompleted` 或提供手工执行动作，否则整段删除。

---

## 五、低优先级问题

- **FIN-22**：`paymentable` 可空，余额支付会真实扣款但不推进任何业务；`payment_orders.ip/user_agent` 从不写入，风控取证缺失。

---

## 六、旧问题状态（2026-11-01 → 2026-11-14）

| 旧编号 | 状态 | 说明 |
|---|---|---|
| FIN-01 | 仍存在 | 租户为 null 仍静默全表 |
| FIN-02 | 仍存在 | `PaymentableResolver` 仍是裸 `find()` |
| FIN-03 | 仍存在 | 额度统计无 `withTrashed` |
| FIN-04 | 部分改善 | 支付单有行锁；充值单仍无锁/无条件更新 |
| FIN-05 | 仍存在 | 锁按支付单，无失败计数 |
| FIN-06 | 仍存在 | 提现无 `tenant_id` |
| FIN-07 | 仍存在 | Voucher 链路死代码 |
| FIN-08 | 仍存在 | 失败日志仍在事务内 |
| FIN-09 | 仍存在 | 无效任务类返回 null |
| FIN-10 | 仍存在 | 只捕 `Exception`，无超时回收 |
| FIN-11 | 仍存在（有变化） | 退款改 `orderByDesc('status')`，但停用配置仍无法退款 |
| FIN-12 | 仍存在 | 无渠道流水号、不校验金额 |
| FIN-13 | 仍存在 | 空 `order_ids` 仍 TypeError |
| FIN-14 | 仍存在 | 无唯一约束、无条件更新 |
| FIN-15 | 仍存在 | 计划强删级联凭据 |
| FIN-16 | 仍存在 | 租户端新建账户空表单 |
| FIN-17 | 部分修复 | `unfreeze()` 已正确；完成日志与 `fee` 未修 |
| FIN-18 | 仍存在 | 自助退款不看订单/售后状态 |
| FIN-19 | 有变化（更危险） | 后端表单已可改金额/状态 |
| FIN-20 | **已修复** | 推荐奖励已补空值保护 |
| FIN-21 | 仍存在 | 提现列表非法 status 500 |
| FIN-22 | 仍存在 | 裸支付单 / 无 ip-ua |
| FIN-23 | 仍存在 | `plans.alias` 全局唯一 |

**新增**：FIN-24（开票必报错）、FIN-25（租户看板跨租户聚合）、FIN-26（提现单可删导致冻结资金卡死）。

---

## 七、修复优先级

1. **P0**：FIN-01（走错商户）、FIN-03（重复退款）、FIN-04（充值重复入账）、FIN-24（开票不可用）。
2. **P1**：FIN-02（支付越权）、FIN-26（资金卡死）、FIN-05（支付密码）、FIN-13（空订单开票）。
3. **P2**：FIN-25/06/11/12/14（租户口径、对账、并发）、FIN-18/19（退款旁路、发票编辑）。
4. **P3**：FIN-07/08/09/10/15/17/21/22/23。
