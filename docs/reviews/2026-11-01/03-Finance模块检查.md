# Finance 模块 检查报告

- **检查日期**：2026-11-01
- **检查范围**：`app/Models/Finance/*`、`app/Services/Finance/*`、Finance 的 Controller / Request / Resource、Jobs / Listeners / Notifications / Policies、`app/Filament/Tenant/Clusters/Finance/**`、`app/Support/Tasks/*`、`routes/apis/finance.php`、Finance 相关迁移
- **检查方式**：逐文件静态走读 + 迁移/框架源码交叉核对（Filament v5 批量授权、Laravel Pipeline、`BelongsToTenant` 作用域）+ 现有 30+ Finance 测试用例比对
- **结论**：资金主链路（余额支付、支付退款状态机）实现质量较好，行锁、条件更新、幂等出口都有考虑；但存在 **1 处资损级缺口、5 处高风险的租户/越权/并发问题**，且结算（Voucher）子系统整体未接线。

> 说明：本报告只做只读审计，未修改任何文件，未运行 `pint`。

---

## 一、模块概览

### 模型

| 模型 | 职责 | 关键点 |
|---|---|---|
| `PaymentOrder` | 支付单，`paymentable` 多态指向订单/充值单 | `AutoCreateOrderNo`；`creating` 强制 `Pending`；**无渠道流水号列** |
| `PaymentRefund` | 支付退款单（资金侧闭环） | `SoftDeletes`；`creator`/`source` 多态 |
| `RechargeOrder` | 充值单（余额/积分） | `ShouldPayment`；`getTotalAmount()` 返回格式化字符串 |
| `WithdrawOrder` | 提现单 | **无 `tenant_id`**、无 `BelongsToTenant` |
| `UserAccount` | 用户钱包（余额/积分/冻结/支付密码） | `#[Table(key:'user_id')]` + `WithoutIncrementing`；**无 `tenant_id`，钱包全局共享** |
| `UserAccountLog` | 资金变动流水 | `before/after` unsigned |
| `Voucher` / `VoucherLog` | 结算凭据与执行日志 | 单号 `Sov-YYYYMMDD+6位` |
| `Plan` / `Task` | 结算计划与步骤（`service` 挂任务类） | `Task.options` 为 json |
| `InvoiceApplication` / `Invoice` / `InvoiceTitle` | 发票申请/发票/抬头 | 抬头快照冻结 |
| `InvoiceApplicationOrder` | 申请-订单中间表 | 仅 `(application,order)` 唯一 |

### 服务

| 服务 | 职责 | 评价 |
|---|---|---|
| `UserAccountService` | 资产增减/冻结解冻/支付密码 | 行锁重读 + 事务，全模块正向样板 |
| `PaymentService` | 余额支付、`markPaidWithBusiness` 统一出口 | 支付单行锁 + 业务推进同事务，设计较好 |
| `PaymentRefundService` | 申请→审核→原路退回→完成/失败/重试 | 条件更新抢占执行权规范；额度统计漏软删除 |
| `RechargeService` | 创建/标记支付/完成入账 | 无锁、无条件更新 |
| `WithdrawService` | 提现冻结/审核/打款/取消 | 有行锁；日志口径错误、`fee` 死参数 |
| `SettlementService` | Pipeline 顺序执行 Plan 下的 Task | 事务边界与日志保留有问题 |
| `VoucherService` | 创建凭据 + 延迟投递自动执行 Job | **无任何调用方（死代码）** |
| `TaskService` | 任务类注册/解析 | 静态注册表 |
| `InvoiceService` | 开票申请校验、开票、拒绝 | 校验较完整；并发与事务缺口 |
| `PaymentableResolver` | type 白名单 → 模型 → 应付金额 | `find()` 无归属校验 |

**统计：严重 1 / 高 5 / 中 12 / 低 5（共 23 条）。**

---

## 二、严重问题

### FIN-01 ｜ 严重 ｜ 多租户 / 一致性
**充值/支付单 `tenant_id` 恒为 null，微信商户配置退化为「任意租户」**

- **位置**：`app/Http/Controllers/Finance/RechargeController.php:44-51`、`app/Models/Traits/BelongsToTenant.php:30-38`、`app/Http/Controllers/Finance/PaymentController.php:179`、`:223`、`database/migrations/0001_00_00_000002_create_users_table.php:14-27`

```php
// RechargeController.php:46
tenantId: Auth::user()?->tenant_id,
```
```php
// BelongsToTenant.php
protected function ofTenant(Builder $query, Tenant|int|null $tenant = null): void
{
    if (is_int($tenant)) { $query->where('tenant_id', $tenant); }
    elseif ($tenant instanceof Tenant) { $query->whereBelongsTo($tenant); }
    // null 时什么都不做
}
```

**已验证事实**：`users` 表没有 `tenant_id` 列（用户与租户是多对多 `user_tenant`），`Auth::user()->tenant_id` 恒为 `null`；生产测试 `TenantTest > tenant has many users` 也直接报 `table users has no column named tenant_id`。

**失败场景**：
1. API 创建的 `recharge_orders.tenant_id = NULL`，派生出的 `payment_orders.tenant_id` 也为 `NULL`；
2. `WechatPayment::ofTenant(null)` 作用域整体失效，`->first()` 取到**全表任意租户**的商户配置；
3. 下单可能用 A 租户商户号，回调时又按空作用域取到 B 租户 → 验签失败 → 永远 `FAIL`，微信无限重试，充值永不入账；退款同理可能打错商户；
4. 充值单在租户端不可见、不可对账。

**建议**：`ofTenant(null)` 必须显式拒绝（返回空查询或抛异常），不能静默全表；充值/支付单租户由上下文显式传入；`PaymentController::store` 对 `tenant_id === null` 直接拒绝微信支付。

---

## 三、高优先级问题

### FIN-02 ｜ 高 ｜ 越权（IDOR）/ 多租户
**支付单创建不校验 `paymentable` 归属与租户**

- **位置**：`app/Http/Controllers/Finance/PaymentController.php:41-71`、`app/Services/Finance/PaymentableResolver.php:52-66`

```php
$paymentable = PaymentableResolver::resolve(
    $request->validated('paymentable_type'),
    (int) $request->validated('paymentable_id'),
);
...
'user_id' => Auth::id(),
'tenant_id' => $paymentable?->tenant_id,
```
```php
// PaymentableResolver.php
return $class::find($id);   // 无 user_id / tenant_id 约束
```

**失败场景**：任意登录用户 `POST /payments` 传 `paymentable_type=order&paymentable_id=<他人订单ID>`。API 走 Sanctum，不启动 Filament 面板，没有租户全局作用域，`Order::find()` 直接命中他人订单。后果：生成挂在自己名下、`tenant_id` 取自受害者订单的支付单；继续余额支付时会推进**他人订单**状态、扣库存、写他人订单日志，或用微信把款打进另一租户商户号。

**建议**：`PaymentableResolver::resolve()` 增加 `userId`/`tenantId` 参数并强制约束；控制器层校验 `$paymentable->user_id === Auth::id()`；`paymentable` 改为必填。

### FIN-03 ｜ 高 ｜ 资金正确性（资损）
**可退额度统计忽略软删除，删除退款记录后可重复退款**

- **位置**：`app/Services/Finance/PaymentRefundService.php:51-58`、`app/Models/Finance/PaymentRefund.php:26-32`、`app/Filament/Tenant/Clusters/Finance/Resources/Refunds/Tables/RefundsTable.php:67-80`

```php
$refunded = $payment->refunds()
    ->whereIn('status', self::OCCUPIED_STATUSES)
    ->sum('amount');
return bcsub((string) $payment->amount, (string) $refunded, 2);
```
```php
Actions\DeleteAction::make(),
...
Actions\DeleteBulkAction::make(),
```

**失败场景**：`PaymentRefund` 使用 `SoftDeletes`，`$payment->refunds()` 默认排除软删记录。一笔 1000 元支付单全额退款后，财务在列表点「删除」（软删），`refundableAmount()` 重新变回 `1000.00`，可再退一次 → **同一笔钱退两次**，审计链也被抹掉。

**建议**：额度统计必须 `->withTrashed()`；资金类单据移除删除动作，改为「作废」状态；或按 `payment_order_id` 汇总占用额做数据库级约束。

### FIN-04 ｜ 高 ｜ 并发一致性
**充值完成链路无行锁、无条件更新，可重复入账**

- **位置**：`app/Services/Finance/RechargeService.php:70-108`、`:121-131`

```php
public function markPaid(RechargeOrder $order, ?string $paymentNo = null): bool
{
    if ($order->status !== RechargeOrderStatus::Pending) {
        throw new InvalidArgumentException('充值订单状态不正确');
    }
    return $order->update(['status' => RechargeOrderStatus::Paid, ...]);
}
```

**失败场景**：`payment_orders` 对 `(paymentable_type, paymentable_id)` 没有唯一约束，同一充值单可创建多张支付单。`PaymentService::markPaidWithBusiness` 只锁「支付单行」，两张支付单是两行，可并发通过：
- 两个线程都读到 `RechargeOrder.status = Pending` → 都置 `Paid`；
- 接着都调用 `complete()`（各自实例状态都是 `Paid`）→ **都执行 `modifyAsset(+received_amount)`** → 余额翻倍。

**建议**：`markPaid` 改条件更新（受影响行数为 0 即失败）；`complete()` 事务内 `lockForUpdate()` 重读并条件更新；`user_account_logs` 加 `(source_type, source_id, type)` 唯一约束做幂等。

### FIN-05 ｜ 高 ｜ 认证安全
**6 位支付密码无失败计数，按资源加锁可被绕过**

- **位置**：`routes/apis/finance.php:41-47`、`app/Http/Middleware/EnsureSingleRequest.php:46-53`、`app/Http/Requests/Finance/PayPaymentRequest.php:15-19`

```php
$router->post('{payment}/pay', [...])
    ->middleware('lock:payment_pay,10,resource,payment');
```
```php
'resource' => $prefix.'_'.$this->resolveRouteParam($request, $paramName),
```

**失败场景**：锁 key 为 `payment_pay_{paymentId}`，攻击者先批量创建 N 张 `Pending` 支付单，再对这 N 个 ID 并发试不同密码，即可获得 N 倍猜测次数。6 位数字密码可被枚举。

**建议**：支付密码校验增加**按用户**的失败计数与锁定（Redis/DB），锁定 key 用 `Auth::id()`；对该接口挂按用户限流器。

### FIN-06 ｜ 高 ｜ 多租户 / 数据模型
**提现单无 `tenant_id`，租户端也没有提现/充值入口**

- **位置**：`database/migrations/0001_01_00_000001_create_user_accounts_table.php:123-129`、`app/Services/Finance/WithdrawService.php:86-96`、`app/Models/Finance/WithdrawOrder.php:21-25`

```php
Schema::create('withdraw_orders', static function (Blueprint $table) {
    $table->comment('提现订单表');
    $table->id();
    $table->string('no', 32)->unique();
    $table->user();
    // 没有 tenant()
});
```

**失败场景**：`recharge_orders` 有 `tenant()`，`withdraw_orders` 没有，模型也不带 `BelongsToTenant`。资金流出（提现）与流入（充值）、退款口径不一致：无法按租户统计应收/应付/已提现，无法做租户级对账；`app/Filament/Tenant/Clusters/Finance` 下没有 Withdraw/Recharge 资源，租户看不到自己钱包的进出。

**建议**：增加 `tenant_id` + `BelongsToTenant`，`create()` 显式写入当前租户；补齐租户端只读/审批页面；若确为平台级钱包，需在文档与代码中明确。

---

## 四、中优先级问题

| 编号 | 问题 | 位置 | 说明与建议 |
|---|---|---|---|
| FIN-07 | 结算（Voucher）链路无任何调用方，分销奖励永不发放 | `app/Services/Finance/VoucherService.php:29-59`、`app/Jobs/Finance/VoucherAutoRunJob.php:17-25` | 全仓库 `VoucherService::create` 无调用方，`OrderCompleted` 也无监听器接入 → Settlement/Plan/Task/Voucher 整套不可达。接入 `OrderCompleted` 或删除整套代码。 |
| FIN-08 | 结算失败日志被事务回滚吞掉 | `app/Services/Finance/SettlementService.php:45-67`、`:100-117` | `failure` 日志先写库，随后 `DB::rollBack()` 一并回滚，只剩 `started`；`Voucher.exception` 只有字符串。失败日志应在回滚后补写或用独立连接。 |
| FIN-09 | Task `service` 类不存在时 Pipeline 收到 `null` | `app/Services/Finance/SettlementService.php:84-89` | `map()` 返回 `null` → `Pipeline::through([...null])` 抛 `TypeError`，真实原因只是脏配置。应 `filter()` 或抛带 task 信息的异常。 |
| FIN-10 | 凭据卡在 `Processing` 无超时回收；Job 只捕 `Exception` | `SettlementService.php:34-41`、`VoucherAutoRunJob.php:19-24` | 进程被杀后永久 `Processing`，无法重试；`catch (Exception)` 捕不到 `Error/TypeError`。加 `processing_at` + 超时回收，改 `catch (Throwable)`。 |
| FIN-11 | 微信商户配置选取口径两套 | `PaymentController.php:179`、`:223` vs `PaymentRefundService.php:422-429` | 支付用 `first()`（可能取到停用配置），退款用 `orderByDesc('status')`。抽 `activeForTenant()` 统一。 |
| FIN-12 | 两支付通道金额口径不一致，渠道流水号未落库 | `PaymentService.php:69`、`:87`、`PaymentController.php:238-245`、`create_payments_table.php:13-49` | 余额支付按「支付瞬间重算金额」扣款并回写；微信回调不校验 `amount.total`；`payment_orders` 无 `transaction_id`。建议创建时快照应付金额、回调做金额比对、补对账字段。 |
| FIN-13 | 发票申请不带 `order_ids` 必然 `TypeError` 500 | `app/Http/Controllers/User/InvoiceController.php:102-109`、`InvoiceService.php:46-68`、`InvoiceApplicationRequest.php:17-21` | `order_ids` 为 `nullable`，不传时 `value()` 返回 null，传给 `int $tenantId` → `TypeError`；`amount` 分支又没校验。应改为必填或补齐按金额开票分支。 |
| FIN-14 | 发票重复开票无唯一约束、无事务 | `InvoiceService.php:50-72`、`:155-166`、`:202-211`、`create_invoices_tables.php:82-92` | 并发可通过 `$alreadyInvoiced`；`issue()` 先读后写无锁；`create + sync` 非原子。建议 `order_id` 唯一索引 + 条件更新 + 事务。 |
| FIN-15 | 强制删除计划会级联删除结算凭据 | `PlansTable.php:48-54`、`create_settlements_table.php:54-57` | 单条删除有保护，但 `ForceDeleteBulkAction` 无保护，数据库 `cascadeOnDelete` 直接删 `vouchers`（含已发奖凭据）。改 `restrictOnDelete` 并移除强制删除。 |
| FIN-16 | 租户端「新建账户」没有表单，保存必失败 | `ManageAccounts.php:12-18`、`AccountResource.php:20-58` | `CreateAction` + 空 `form()` + `user_id` 主键无默认值 → 500。移除入口或补 `user_id` 选择与默认值。 |
| FIN-17 | 提现完成日志 `before/after` 记反、`fee` 从未生效 | `WithdrawService.php:170-185`、`:48-52`、`WithdrawController.php:78-86` | 只扣 `frozen_balance`，日志却写成可用余额减少；`fee` 无来源恒为 0。改为冻结语义日志并真正传入费率。 |
| FIN-18 | 用户可对已发货/已签收订单直接申请退款 | `PaymentController.php:100-116`、`PaymentRefundService.php:89-99` | 只校验支付单 `Paid` 与额度，不看订单状态/售后单 → 绕过售后的旁路退款入口。建议只允许「本人 + 售后单 + 审核通过」发起。 |
| FIN-19 | 发票申请页存留「编辑」动作但无表单 | `InvoiceApplicationsTable.php:46-52`、`InvoiceApplication.php:19-21` | 空表单提交无副作用；若补表单则可直接改已开票金额/状态。移除编辑动作或白名单字段。 |
| FIN-20 | 推荐奖励任务对 `user->relation` 无空值保护 | `app/Support/Tasks/DirectReward.php:31-38`、`SecondReward.php:31-38` | 无关系行的用户 → `Attempt to read property "parent" on null`（`Error`）。加空值保护 + `loadMissing`。 |
| FIN-21 | 提现列表 `status` 未校验，非法值 500 | `WithdrawController.php:32-40` | `WithdrawOrderStatus::from('abc')` 抛 `ValueError`。改用 `Rule::enum` / `tryFrom`。 |
| FIN-22 | 支付创建不落 `ip`/`user_agent`，允许无业务主体的裸支付单 | `PaymentController.php:55-71`、`create_payments_table.php:37-42` | 风控取证缺失；余额支付会真实扣款但无任何业务推进。补字段写入、`paymentable` 必填、未知主体抛异常并退款。 |
| FIN-23 | `plans.alias` 是全局唯一，但计划是租户维度 | `create_settlements_table.php:14-19` | 两个租户不能使用相同计划别名（如都叫 `default`），第二个租户保存时报唯一键冲突。改为 `unique(['tenant_id','alias'])`（迁移需处理已有数据）。 |

---

## 五、测试缺口

现有 Finance 测试覆盖了幂等、余额不足、冻结/解冻、退款状态机（`PaymentServiceTest`、`PaymentRefundServiceTest`、`UserAccountServiceTest`、`WithdrawServiceTest`、`InvoiceServiceTest`），但以下高风险路径**没有用例**：

| 缺口 | 对应问题 |
|---|---|
| 充值/支付单 `tenant_id` 落库断言 | FIN-01 / FIN-06 |
| 用他人 `paymentable_id` 创建支付单应 403/404 | FIN-02 |
| 软删除退款单后再退款应被拒绝 | FIN-03 |
| 同一充值单两张支付单并发只入账一次 | FIN-04 |
| 支付密码连续失败应锁定 | FIN-05 |
| 结算 Task 类不存在 / 用户无 relation 的失败路径 | FIN-09 / FIN-20 |
| 发票申请不带 `order_ids` 的接口行为 | FIN-13 |
| 并发开票只产生一张发票 | FIN-14 |

---

## 六、修复优先级

1. **P0（当周）**：FIN-03（资损）、FIN-01（走错商户/回调全废）、FIN-02（越权）、FIN-04（重复入账）。
2. **P1（两周）**：FIN-05、FIN-06、FIN-08 / FIN-09 / FIN-10、FIN-12。
3. **P2**：FIN-07（接线或删除）、FIN-11、FIN-13、FIN-14、FIN-15。
4. **P3**：FIN-16 ~ FIN-22（UI/校验/日志口径）。
