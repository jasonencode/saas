# 支付服务实现说明

本文记录支付单（`App\Models\Finance\PaymentOrder`）与支付退款单（`App\Models\Finance\PaymentRefund`）的实现约定，供接口联调、对账与排障使用。

## 一、入口与职责

| 入口 | 说明 |
|------|------|
| `PaymentController::store()` | 创建支付单，必须关联 `order` 或 `recharge`，金额以业务模型为准 |
| `PaymentController::pay()` | 按 `gateway` 分流：`balance` 走余额，`wechat` 返回 JSAPI 参数 |
| `PaymentController::notify()` | 微信支付回调，验签 + 校验金额/币种/商户号后推进业务 |
| `PaymentController::refundNotify()` | 微信退款回调，验签 + 校验退款金额后完成/失败退款单 |
| `PaymentService::markPaidWithBusiness()` | 余额支付与微信回调的统一出口，行锁 + 幂等 |
| `PaymentRefundService` | 支付退款单创建、审核、执行、补完成、重试 |
| `ReconcilePaymentRefundsCommand` | 微信退款对账，处理 `processing` 与 `failed` |
| `ExpirePaymentOrdersCommand` | 关闭超时未支付的支付单/充值单，并尽力关闭微信订单 |

## 二、支付单状态

```
pending ──余额支付/回调成功──> paid ──全额退款完成──> refunded
   │
   ├── 订单取消/删除/超时 ──> canceled（渠道晚到收款会置 paid 并自动退款）
   └── 创建新单/复用后保持 pending
```

- `paid_amount` 记录渠道实收金额，旧数据为空时回退 `amount`。
- `transaction_id` 记录渠道流水号，回调/对账优先使用。
- 全额退款后支付单转 `refunded`；部分退款保留 `paid`，可退额度按实收金额递减。

## 三、退款单状态

```
pending ─审核通过─> approved ─执行─> processing ─渠道成功─> completed
   │                    │                │
   │                    │                └─渠道关闭/异常─> failed（可重试）
   │                    └─审核拒绝─> rejected
   └─取消─> cancelled
```

- 创建、审核、执行均使用条件更新抢占，防止并发重复处理。
- `failed` 退款收到 SUCCESS 回调或对账确认成功时允许补完成（`failed → completed`）。
- 退款金额不得超过实收金额扣除已占用退款（含软删记录）后的余额。

## 四、幂等约定

| 场景 | 幂等键/机制 |
|------|-------------|
| 微信重复回调 | 支付单行锁 + `paid`/`refunded` 早退 |
| 余额支付重复提交 | 支付单行锁 + `user_account_logs.idempotency_key = payment_order:{id}:consume` |
| 充值到账 | 充值单行锁 + `idempotency_key = recharge_order:{id}:recharge` + 条件更新 |
| 余额退款 | `idempotency_key = payment_refund:{id}:refund` + 先查后加 |
| 退款执行 | `approved → processing` 条件更新抢占 |
| 退款完成/失败 | 仅允许 `processing`/`failed` → `completed`、`processing` → `failed`，不覆盖终态 |

## 五、微信商户配置

- 支付单创建时记录 `wechat_payment_id`（微信支付），支付、回调、退款、对账统一通过 `WechatPaymentResolver::forPayment()` 取配置。
- 租户为空时不会回退到全平台任意配置；无租户上下文禁止创建微信支付单。
- 支付/下单只使用启用配置；回调、退款、对账允许使用已停用的原配置（原路退回不依赖启用开关）。

## 六、回调校验

支付回调必须同时满足：

1. 支付单 `gateway = wechat`；
2. `out_trade_no` 与支付单号一致；
3. `amount.total`（分）换算后与支付单金额一致，`currency = CNY`；
4. `mchid` 与锁定配置一致，`appid`（存在时）与公众号配置一致。

退款回调额外校验 `out_refund_no`、`amount.refund` 与退款单金额一致。任一不一致写 `event_logs` 审计并返回 `FAIL`，不会推进支付/退款状态。

## 七、异常收款处理

以下场景由 `PaymentService` 自动创建退款单，并在事务提交后自动审核 + 执行（失败保留退款单供财务重试），同时给用户发站内信：

- 支付成功后订单已取消/已流转；
- 支付成功后扣库存失败（先取消订单）；
- 关联业务已删除；
- 订单缺少所属用户；
- 同一充值单已由其他支付单完成。

## 八、运行约束

- `yansongda/pay` 的 `Pay::config()` 为进程级全局配置，同一进程不可并发使用不同商户；迁移到 Octane/Swoole 前需按请求隔离。
- 微信回调接口使用资源锁 + IP 限流；渠道重复投递以支付单/退款单状态幂等。
