# 支付单状态流转图

> 支付单状态（`App\Enums\Finance\PaymentStatus`）记录一次支付请求的生命周期，模型 `App\Models\Finance\PaymentOrder`。该枚举**未实现 `HasStateMachine`**，没有 `next()` / `previous()`，流转合法性由各入口的状态前置校验自行维护。支付单通过 `paymentable` 多态关联业务（商城订单 `Order` / 充值订单 `RechargeOrder`），支付成功时由 `PaymentService` 联动推进业务单据。各网关的支付动作见 `App\Http\Controllers\Finance\PaymentController`。

## 一、状态说明

| 状态 | 枚举 | 值 | 颜色 | 说明 |
|------|------|-----|------|------|
| 待支付 | Pending | `pending` | amber | 支付单已创建，等待用户支付 |
| 支付处理中 | Processing | `processing` | sky | 已调用网关、等待结果（**未实现**） |
| 已支付 | Paid | `paid` | emerald | 支付成功，联动推进业务单据 |
| 支付失败 | Failed | `failed` | red | 网关返回失败（**未实现**） |
| 已取消 | Canceled | `canceled` | rose | 超时或用户取消（**未实现**） |
| 已退款 | Refunded | `refunded` | neutral | 已全额退款（**未实现**） |

---

## 二、状态流转图

### 2.1 已实现链路

```mermaid
stateDiagram-v2
    [*] --> Pending : store() 创建支付单<br/>（模型 boot 置 Pending，30 分钟过期）

    Pending --> Paid : pay() 余额支付<br/>PaymentService::payByBalance()
    Pending --> Paid : notify() 微信支付回调<br/>trade_state = SUCCESS

    Paid --> [*]

    classDef pending fill:#f59e0b,stroke:#d97706,color:white
    classDef paid fill:#10b981,stroke:#059669,color:white

    class Pending pending
    class Paid paid
```

**状态流转：**

- Pending → Paid
  - 余额支付：`POST /payments/{payment}/pay` → `payBalance()` → `PaymentService::payByBalance()`
  - 微信支付：前端凭 `payWechat()` 返回的支付参数完成付款后，微信侧回调 `POST /payments/{payment}/notify`（免登录路由）
- 两个入口都只处理 `Pending`：`pay()` 先校验 `Pending` 且未过期，`payByBalance()` 在事务内校验支付密码
- **支付成功后的业务联动统一走 `PaymentService::markPaidWithBusiness()`（2026-09-11 起余额支付与回调共用同一出口）**：
  - 先把支付单置为 `Paid` + `paid_at`
  - `paymentable` 为 `Order` → `OrderService::pay()`，把商城订单推进到 `Paid` / `PickupPending` / `Completed`（见 [订单状态流转图](order-status.md)）
  - `paymentable` 为 `RechargeOrder` → `RechargeService::markPaid()` + `complete()`，余额 / 积分入账（见 [充值状态流转图](recharge-status.md)）
- **幂等：** 支付单已是 `Paid` 时 `markPaidWithBusiness()` 直接返回，微信重复投递回调不会重复推进业务
- **同成败：** 回调侧把「标记已支付 + 推进业务」包在同一个 `DB::transaction` 内，任一步失败整体回滚并返回 `FAIL`，由微信重试

### 2.2 未实现状态

```mermaid
stateDiagram-v2
    [*] --> Pending : store() 创建支付单

    Pending --> Paid : 已实现

    state "已取消 Canceled（未实现）" as Canceled
    state "支付处理中 Processing（未实现）" as Processing
    state "支付失败 Failed（未实现）" as Failed
    state "已退款 Refunded（未实现）" as Refunded

    Pending -.-> Canceled : 超时（无定时任务）
    Pending -.-> Processing : 网关异步（无写入方）
    Pending -.-> Failed : 网关失败（无写入方）
    Paid -.-> Refunded : 全额退款（无写入方）

    classDef pending fill:#f59e0b,stroke:#d97706,color:white
    classDef paid fill:#10b981,stroke:#059669,color:white
    classDef missing fill:#e5e7eb,stroke:#9ca3af,color:#374151
    classDef todo fill:#f3f4f6,stroke:#d1d5db,color:#6b7280

    class Pending pending
    class Paid paid
    class Canceled missing
    class Processing todo
    class Failed todo
    class Refunded todo
```

---

## 三、状态流转规则

无状态机约束，下表为**各入口校验后代码实际允许**的流转（校验位置见末列）。

| 当前状态 | 可流转到 | 触发 | 校验位置 |
|----------|----------|------|----------|
| Pending | Paid | 余额支付 | `PaymentController::pay()`（`app/Http/Controllers/Finance/PaymentController.php:143`） |
| Pending | Paid | 微信回调 | `PaymentController::notify()`（:252） |
| Pending | -（无入口） | 超时 / 网关失败 / 异步处理中 | 未实现 |
| Paid | -（无入口） | 退款 | 未实现 |
| Processing / Failed / Canceled / Refunded | - | - | 未实现，无任何写入方 |

> **实现缺口：** `Processing` / `Failed` / `Canceled` / `Refunded` 四个状态**没有任何代码写入**。`expired_at` 只在 `pay()` 里做「是否已过期」的前置校验，没有定时任务把过期支付单改成 `Canceled`。

---

## 四、操作对应状态流转

来源：`App\Http\Controllers\Finance\PaymentController`、`App\Services\Finance\PaymentService`。

| 操作 | 方法 | 入口 | 前置状态 | 后置状态 |
|------|------|------|----------|----------|
| 创建支付单 | `store()` | `POST /payments` | - | Pending（`expired_at` = 当前 +30 分钟） |
| 查询支付单 | `show()` | `GET /payments/{payment}` | 任意 | 不变 |
| 发起支付 | `pay()` → `payBalance()` / `payWechat()` | `POST /payments/{payment}/pay` | Pending 且未过期 | 余额：Paid / 微信：返回支付参数（不落状态） |
| 余额支付 | `PaymentService::payByBalance()` | 由 `payBalance()` 调用 | Pending | Paid + 联动业务单据 |
| 微信支付回调 | `notify()` | `POST /payments/{payment}/notify` | Pending | Paid + 业务联动（订单推进 / 充值到账） |
| 申请退款 | `refund()` | `POST /payments/{payment}/refund` | Paid | 不变（创建 `PaymentRefund`，见 [支付退款单状态流转图](payment-refund-status.md)） |

**网关分流：** `pay()` 按 `gateway` 匹配（`PaymentGateway::Balance` → `payBalance()`，`PaymentGateway::Wechat` → `payWechat()`），其余网关返回「暂不支持该支付方式」。

---

## 五、资金与副作用

| 时点 | 副作用 | 位置 |
|------|--------|------|
| 余额支付成功 | 扣减 `user_accounts.balance`，写 `user_account_logs`（`UserAccountLogType::Consume`，备注 `支付单号# {no}`） | `PaymentService::payByBalance()`（`app/Services/Finance/PaymentService.php:62`） |
| 余额支付成功 | 支付单金额与实扣金额对齐后写库（防止客户端伪造金额） | 同上（:72） |
| 支付成功（关联订单） | 推进商城订单状态（`Paid` / `PickupPending` / `Completed`），触发订单侧下游事件 | `markPaidWithBusiness()`（`app/Services/Finance/PaymentService.php:107`） |
| 支付成功（关联充值单） | 充值单转 `Paid` 并即时到账（写余额 / 积分 + `user_account_logs`） | 同上（:114） |
| 微信回调成功 | 标记支付单 `Paid` + `paid_at`，并**推进关联业务**（与余额支付共用出口）；重复投递因支付单已 `Paid` 而直接返回 | `PaymentController::notify()`（:252） |

**金额口径：** `payableAmount()`（:99）在关联业务模型时取业务方应付金额（`PaymentableResolver::amountOf()`），仅在无关联时回退支付单自身 `amount`，避免客户端低价买单。

**幂等与并发：**

- 支付密码校验放在 `DB::transaction` **内部**（:55-61），密码错误时事务回滚
- 状态前置校验（`pay()` 的 Pending / 未过期）在事务外，无行锁；并发重复提交依赖「第二次进入时支付单已非 Pending」拦截
- **重复回调幂等：** `markPaidWithBusiness()` 以「支付单是否已 `Paid`」作为幂等键，微信重复投递只会命中一次业务推进；回调侧同时校验 `out_trade_no` 与当前支付单号一致，不匹配直接返回 `FAIL` 且不改任何状态

---

## 六、实现缺口

| 缺口 | 影响 | 相关位置 |
|------|------|----------|
| 无过期关闭任务 | 超过 30 分钟的支付单永远停在 `Pending`，`Canceled` 形同虚设 | 无 `Canceled` 写入方 |
| 无失败 / 处理中回写 | 网关异常、异步支付无状态落点 | `Failed` / `Processing` 无写入方 |
| 无全额退款回写 | 支付单不会转 `Refunded`，需人工比对退款单 | `Refunded` 无写入方 |

> 「微信回调不推进业务单据」已于 2026-09-11 修复（统一出口 `PaymentService::markPaidWithBusiness()`）。

---

## 七、终态说明

| 状态 | 说明 | 是否可删除 |
|------|------|------------|
| Paid | 支付成功，是当前唯一可达终态 | 否（业务单据强关联） |
| Canceled / Failed / Refunded | 枚举已定义，未实现，当前不可能出现 | - |
