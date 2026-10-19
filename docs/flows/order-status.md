# 订单状态流转图

> 商城订单状态机（`App\Enums\Mall\OrderStatus`）按履约方式（`FulfillmentType`）分三条链路：快递邮寄（mail）、门店自提（pickup）、虚拟商品（virtual）。三条链路共享 `Pending` / `Canceled` / `Refunded`，差异集中在付款后的中间段。流转合法性由 `HasStateMachine::canTransitionTo()` 基于 `next()` 判断，`OrderService::assertCan()` 传入订单履约方式校验。

## 一、状态说明

| 状态 | 枚举 | 值 | 颜色 | 所属链路 | 说明 |
|------|------|-----|------|----------|------|
| 待付款 | Pending | `pending` | amber | 共享 | 用户已下单，未付款 |
| 已取消 | Canceled | `canceled` | red | 共享（终态） | 用户未支付取消 / 超时未支付自动取消 |
| 待发货 | Paid | `paid` | blue | mail | 付款完成，等待发货 |
| 备货中 | Preparing | `preparing` | sky | mail | 打印订单、拣货、打包 |
| 部分发货 | PartiallyShipped | `partially` | cyan | mail | 部分商品已发货 |
| 已发货 | Delivered | `delivered` | indigo | mail | 卖家已发货 |
| 已签收 | Signed | `signed` | teal | mail | 用户已签收 |
| 已完成 | Completed | `completed` | emerald | 共享（正常履约终态） | 签收/核销 N 天后完成；virtual 支付即完成 |
| 待自提 | PickupPending | `pickup_pending` | amber | pickup | 付款后进入，等待用户到店核销 |
| 已核销 | Verified | `verified` | teal | pickup | 商家核销通过，等价于 mail 链路的「已签收」 |
| 已退款 | Refunded | `refunded` | gray | 共享（终态） | 全部订单项退款完成（资金已原路退回） |

---

## 二、状态流转图

### 2.1 快递邮寄 (mail)

```mermaid
stateDiagram-v2
    [*] --> Pending : createOrder() 下单

    Pending --> Canceled : cancel() 用户取消 /<br/>AutoCloseOrder 超时自动取消
    Pending --> Paid : pay() 支付成功

    Paid --> Preparing : preparing() 开始备货
    Paid --> PartiallyShipped : deliver() 部分发货（跳级）
    Paid --> Delivered : deliver() 整单发货（跳级）

    Preparing --> PartiallyShipped : deliver() 部分发货
    Preparing --> Delivered : deliver() 整单发货

    PartiallyShipped --> Delivered : deliver() 剩余商品发货
    PartiallyShipped --> Signed : sign() 直接签收

    Delivered --> Signed : sign() 确认签收

    Signed --> Completed : complete() 手动完成 /<br/>签收 N 天后自动完成

    Canceled --> [*]
    Completed --> [*]
    Refunded --> [*]

    classDef pending fill:#f59e0b,stroke:#d97706,color:white
    classDef canceled fill:#ef4444,stroke:#dc2626,color:white
    classDef paid fill:#3b82f6,stroke:#2563eb,color:white
    classDef preparing fill:#0ea5e9,stroke:#0284c7,color:white
    classDef partiallyShipped fill:#06b6d4,stroke:#0891b2,color:white
    classDef delivered fill:#6366f1,stroke:#4f46e5,color:white
    classDef signed fill:#14b8a6,stroke:#0d9488,color:white
    classDef completed fill:#10b981,stroke:#059669,color:white
    classDef refunded fill:#6b7280,stroke:#4b5563,color:white

    class Pending pending
    class Canceled canceled
    class Paid paid
    class Preparing preparing
    class PartiallyShipped partiallyShipped
    class Delivered delivered
    class Signed signed
    class Completed completed
    class Refunded refunded
```

**状态流转：**

- Pending → Canceled（终态）/ Paid
- Paid → Preparing → PartiallyShipped → Delivered → Signed → Completed（正常履约终态）
- Paid 可跳级直接 PartiallyShipped / Delivered（发货不强制先备货）
- PartiallyShipped 可直接 Signed（部分发货后用户直接签收）
- 全额退款完成 → Refunded（终态）；部分退款不改变订单状态

### 2.2 门店自提 (pickup)

```mermaid
stateDiagram-v2
    [*] --> Pending : createOrder() 下单

    Pending --> Canceled : cancel() 用户取消 /<br/>AutoCloseOrder 超时自动取消
    Pending --> PickupPending : pay() 支付成功<br/>（自动进入待自提，生成核销码）

    PickupPending --> Verified : verify() 商家核销<br/>（校验核销码）

    Verified --> Completed : complete() 手动完成 /<br/>核销 N 天后自动完成

    Canceled --> [*]
    Completed --> [*]
    Refunded --> [*]

    classDef pending fill:#f59e0b,stroke:#d97706,color:white
    classDef canceled fill:#ef4444,stroke:#dc2626,color:white
    classDef pickupPending fill:#f59e0b,stroke:#d97706,color:white
    classDef verified fill:#14b8a6,stroke:#0d9488,color:white
    classDef completed fill:#10b981,stroke:#059669,color:white
    classDef refunded fill:#6b7280,stroke:#4b5563,color:white

    class Pending pending
    class Canceled canceled
    class PickupPending pickupPending
    class Verified verified
    class Completed completed
    class Refunded refunded
```

**状态流转：**

- Pending → Canceled（终态）
- Pending → PickupPending → Verified → Completed（正常履约终态）
- 状态机层面为 `Pending → Paid → PickupPending`，但 `pay()` 一步推进，订单不停留在 Paid
- 核销码格式 `PICK-XXXX-XXXX-XXXX`（去除易混淆字符 0/O/1/I），付款时幂等生成
- 退款：待自提（PickupPending）仅退款；已核销（Verified）退货退款；全额完成 → Refunded

### 2.3 虚拟商品 (virtual)

```mermaid
stateDiagram-v2
    [*] --> Pending : createOrder() 下单

    Pending --> Canceled : cancel() 用户取消 /<br/>AutoCloseOrder 超时自动取消
    Pending --> Completed : pay() 支付即完成<br/>（派发 OrderCompleted）

    Canceled --> [*]
    Completed --> [*]
    Refunded --> [*]

    classDef pending fill:#f59e0b,stroke:#d97706,color:white
    classDef canceled fill:#ef4444,stroke:#dc2626,color:white
    classDef completed fill:#10b981,stroke:#059669,color:white
    classDef refunded fill:#6b7280,stroke:#4b5563,color:white

    class Pending pending
    class Canceled canceled
    class Completed completed
    class Refunded refunded
```

**状态流转：**

- Pending → Canceled（终态）
- Pending → Completed（正常履约终态），无中间状态，支付成功直接完成
- 退款：Completed 后支持仅退款；全额完成 → Refunded

### 2.4 完整状态流转总览

```mermaid
stateDiagram-v2
    [*] --> Pending : createOrder() 下单

    Pending --> Canceled : cancel()
    state "pay() 按履约方式分流" as branch <<choice>>
    Pending --> branch : 支付成功

    branch --> Paid : mail
    branch --> PickupPending : pickup
    branch --> Completed : virtual

    Paid --> Preparing
    Paid --> PartiallyShipped
    Paid --> Delivered
    Preparing --> PartiallyShipped
    Preparing --> Delivered
    PartiallyShipped --> Delivered
    PartiallyShipped --> Signed
    Delivered --> Signed
    Signed --> Completed

    PickupPending --> Verified
    Verified --> Completed

    Canceled --> [*]
    Completed --> [*]
    Refunded --> [*]
```

### 2.5 全额退款完成（共享，旁路状态机）

`Refunded` 由 `RefundService` 在支付退款实际到账后直接写入，不经过 `OrderStatus::next()`，因此上图中不绘制退款箭头，单独说明：

| 原状态 | 可退款类型 | 全额退款后 |
|--------|------------|------------|
| mail: Paid / Preparing | 仅退款、退货退款 | Refunded |
| mail: PartiallyShipped / Delivered / Signed | 退货退款 | Refunded（部分退款保持原状态） |
| mail: Completed | 不允许退款 | - |
| pickup: PickupPending | 仅退款 | Refunded |
| pickup: Verified | 退货退款 | Refunded |
| virtual: Completed | 仅退款 | Refunded |
| virtual: 其他状态 | 不允许退款 | - |

> 退款完成前订单保持原状态；`RefundService` 仅在「全部订单项均已退款完成」时写 `Refunded`，并释放订单优惠券。

---

## 三、状态流转规则

来源：`OrderStatus::next()` / `OrderStatus::previous()`（`previous()` 同样按履约方式区分 Completed 的前置状态）。

### 3.1 next()：可流转目标状态

| 当前状态 | mail | pickup | virtual |
|----------|------|--------|---------|
| Pending | Canceled, Paid | Canceled, Paid | Canceled, Paid |
| Paid | Preparing, PartiallyShipped, Delivered | PickupPending | Completed |
| Preparing | PartiallyShipped, Delivered | - | - |
| PartiallyShipped | Delivered, Signed | - | - |
| Delivered | Signed | - | - |
| Signed | Completed | - | - |
| PickupPending | - | Verified | - |
| Verified | - | Completed | - |
| Canceled | -（终态） | -（终态） | -（终态） |
| Completed | -（正常履约终态） | -（正常履约终态） | -（正常履约终态） |
| Refunded | -（终态，由退款流程旁路写入） | -（终态） | -（终态） |

### 3.2 previous()：前置状态

| 当前状态 | 前置状态 |
|----------|----------|
| Canceled | Pending |
| Paid | Pending |
| Preparing | Paid |
| PartiallyShipped | Paid, Preparing |
| Delivered | Paid, Preparing, PartiallyShipped |
| Signed | PartiallyShipped, Delivered |
| Completed | mail: Signed / pickup: Verified / virtual: Paid |
| PickupPending | Paid |
| Verified | PickupPending |
| Refunded | Paid, Preparing, PartiallyShipped, Delivered, Signed, Completed, PickupPending, Verified |

---

## 四、操作对应状态流转

来源：`App\Services\Mall\OrderService`。

| 操作 | 方法 | 前置状态 | 后置状态 |
|------|------|----------|----------|
| 创建订单 | `createOrder()` / `createOrders()` | - | Pending |
| 取消订单 | `cancel()` | Pending | Canceled（回退「下单扣减」的库存并释放券） |
| 支付成功 | `pay()` | Pending | mail: Paid / pickup: PickupPending（生成核销码）/ virtual: Completed；`DeductStockType::Paid` 在此扣库存 |
| 开始备货 | `preparing()` | Paid | Preparing |
| 订单发货 | `deliver()` | Paid, Preparing, PartiallyShipped | 全部发货: Delivered / 部分发货: PartiallyShipped；拒绝重复发货与已全额退款商品 |
| 删除发货记录 | `deleteExpress()` | Paid, Preparing, PartiallyShipped, Delivered | 按剩余发货量回退（见下方说明） |
| 确认签收 | `sign()` | Delivered, PartiallyShipped | Signed（记录 signed_at） |
| 自提核销 | `verify()` | PickupPending | Verified（校验核销码，记录核销人/时间） |
| 完成订单 | `complete()` | mail: Signed / pickup: Verified / virtual: Paid | Completed |
| 删除订单 | `delete()` | Pending, Canceled | 软删除（不改变状态） |

> **发货记录回退（旁路状态机）：** `deleteExpress()` 删除发货记录后按剩余发货量直接改写状态——全部回退时 → `Paid`，部分回退时 → `PartiallyShipped`，全部仍已发货时 → `Delivered`。该回退不经过 `canTransitionTo()` 校验（如 `Delivered → Paid` 不在 `next()` 映射内），属状态机旁路操作。已签收（Signed）及之后的状态不再允许删除发货记录。

---

## 五、退款操作对应状态流转

来源：`RefundService` / `PaymentRefundService`。售后单状态与支付退款单状态相互独立，资金到账后才完成售后。

| 操作 | 方法 | 退款单前置 | 退款单后置 | 说明 |
|------|------|------------|------------|------|
| 提交申请 | `createRefund()` | - | Pending 或 Processing | 仅退款自动审核 → Processing；退货退款 → Pending |
| 审核通过 | `approveRefund()` | Pending | 退货退款: WaitingReturn / 仅退款: Processing | 记录审核人/时间 |
| 审核拒绝 | `rejectRefund()` | Pending | Rejected | 终态 |
| 用户取消 | `cancelRefund()` | Pending | Cancelled | 终态 |
| 寄回商品 | `shipReturn()` | WaitingReturn | Shipping | 记录退货物流 |
| 确认收货 | `confirmReceive()` | Shipping | Processing | 不落库 `Received`，直接进入退款处理 |
| 提交资金退款 | `confirmRefund()` | Processing | Processing（保持） | 创建 `PaymentRefund`（Pending），不提前完成售后 |
| 支付退款到账 | `PaymentRefundService::execute()` / `complete()` | Processing | Completed | 回调 `completeFromPaymentRefund()` 回补库存、释放券、回写订单 |
| 财务驳回 / 取消支付退款 | `PaymentRefundService::reject()` / `cancel()` | Pending/Approved | Failed | 回调 `failFromPaymentRefund()` 将售后单置 Failed |
| 通道失败（可重试） | `PaymentRefundService::execute()` 失败 | Processing | Processing（保持） | 仅记录 RefundLog，等待重试 |
| 重试退款 | `retryRefund()` | Failed | Processing | 需重新提交资金退款 |
| 标记失败 | `markRefundFailed()` | Processing | Failed | 人工终结 |

**退款状态机（`RefundStatus`）：**

```mermaid
stateDiagram-v2
    [*] --> Pending : createRefund()（退货退款）
    [*] --> Processing : createRefund()（仅退款自动审核）

    Pending --> WaitingReturn : approveRefund()（退货退款）
    Pending --> Processing : approveRefund()（仅退款）
    Pending --> Rejected : rejectRefund()
    Pending --> Cancelled : cancelRefund()

    WaitingReturn --> Shipping : shipReturn()
    Shipping --> Processing : confirmReceive()

    Processing --> Completed : 支付退款到账
    Processing --> Failed : 财务驳回 / 标记失败

    Failed --> Processing : retryRefund()

    Rejected --> [*]
    Cancelled --> [*]
    Completed --> [*]
```

> `Received` 枚举值仅为历史兼容保留，当前流程不会写入该状态。

---

## 六、自动化流转

| 任务 | 触发 | 规则 |
|------|------|------|
| `AutoCloseOrder`（延迟任务） | 下单时调度 | 仅 `Pending` 状态执行；超时未支付自动 `cancel()`（超时时间由店铺配置） |
| `OrderAutoCompleteCommand`（`app:mall:order-auto-complete`） | 定时调度 | mail 扫「已签收」、pickup 扫「已核销」，N 天后（店铺配置 `auto_complete_days`，默认 7 天）自动 `complete()`；virtual 支付即完成，无需扫描 |
| `PickupTimeoutCommand`（`app:mall:pickup-timeout`） | 定时调度 | 自提订单超过 `custom.mall.pickup_expired_days`（默认 7 天，`0` 关闭）仍未核销，自动创建全额仅退款并提交资金退款；财务到账后订单转 `Refunded` |

---

## 七、终态说明

| 终态 | 说明 | 是否可删除 |
|------|------|------------|
| Canceled | 未支付取消 / 超时自动取消 | 是（`delete()` 允许 Pending / Canceled 删除） |
| Completed | 正常履约完结；派发 `OrderCompleted` 事件（下游结算监听器当前为空实现，结算尚未接入） | 否 |
| Refunded | 全部订单项退款完成，资金已原路退回 | 否 |
