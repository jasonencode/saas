# 商城订单流程时序图

> 基于 `app/Services/Mall/OrderService.php`、`RefundService.php`、`app/Services/Finance/PaymentService.php`、
> `PaymentRefundService.php` 梳理，配合 [order-status.md](order-status.md)（状态机）阅读。
>
> 文中「提交后派发」指事件实现 `ShouldDispatchAfterCommit`：事务提交后才执行监听器；事务回滚则事件不会派发。

## 1. 下单（跨租户拆单）

```mermaid
sequenceDiagram
    autonumber
    participant U as 用户
    participant OS as OrderService
    participant DB as 数据库(事务)
    participant CS as CouponService
    participant DL as DeliveryService
    participant EQ as 事件/队列

    U->>OS: createOrders(items, 履约方式, 地址, 自提点, 券ID)
    OS->>OS: 按 tenantId 分组
    OS->>OS: 校验优惠券归属租户(否则整单拒绝)
    OS->>DB: BEGIN(外层事务, 原子拆单)
    loop 每个租户
        OS->>OS: createOrder(校验履约方式/自提点/地址归属)
        OS->>DB: SAVEPOINT 嵌套事务
        OS->>DL: calculateOrderFreight(仅 mail+Sku 计运费)
        OS->>DB: 创建 Order + OrderItem
        opt 下单即扣库存 (DeductStockType::Ordered)
            OS->>DB: orderable->deductStock(qty)
        end
        Note over OS,DB: DeductStockType::Paid 下单不扣库存，延迟到支付成功时扣减
        opt 有优惠券
            OS->>CS: applyToOrder(核销, 失败回滚整单)
        end
        OS->>DB: 快照收货地址(OrderAddress)
        OS->>DB: 用户↔租户关联 syncWithoutDetaching
        OS->>DB: 写 OrderLog(Created)
        OS->>EQ: dispatch OrderCreated(提交后派发)
    end
    OS->>DB: COMMIT(任一租户失败全部回滚)
    OS-->>U: 返回订单集合
```

## 2. 支付（按履约方式分流）

```mermaid
sequenceDiagram
    autonumber
    participant U as 用户/支付回调
    participant PS as PaymentService
    participant OS as OrderService
    participant DB as 数据库
    participant T as 租户
    participant PR as PaymentRefundService
    participant EQ as 事件

    U->>PS: markPaidWithBusiness(payment)
    PS->>DB: 行锁重读支付单 + 幂等检查
    PS->>DB: 支付单 status=Paid, paid_at
    PS->>OS: pay(order)

    OS->>DB: BEGIN + 行锁重读订单
    alt 订单已非 Pending（已取消/已退款等）
        OS-->>PS: OrderNotPayableException
        PS->>PR: create(order, 全额, 原因=订单不可支付)
        Note over PS,PR: 生成待审核原路退款，回调返回成功，避免渠道无限重试
    else 订单仍为 Pending
        OS->>OS: assertCan(Pending→Paid)
        opt DeductStockType::Paid
            OS->>DB: orderable->deductStock(qty)
            Note over OS,DB: 扣减失败抛 InsufficientStockException，整笔支付回滚
        end
        OS->>OS: 按履约方式选目标状态<br/>mail→Paid / pickup→PickupPending / virtual→Completed
        OS->>DB: 更新 status, paid_at
        opt pickup
            OS->>OS: 生成核销码 PICK-XXXX-XXXX-XXXX(幂等)
        end
        OS->>DB: 写 OrderLog(Paid)
        OS->>T: notify(NewOrderToTenant)
        OS->>EQ: dispatch OrderPaid(提交后派发)
        opt virtual
            OS->>EQ: dispatch OrderCompleted(支付即完成, 提交后派发)
        end
    end
    OS->>DB: COMMIT
```

若付款时库存不足，`PaymentService` 会调用 `OrderService::cancel()` 关闭订单，再生成原路退款，避免「钱已收、货没有」。

## 3. 发货 → 签收 → 完成（快递邮寄）

```mermaid
sequenceDiagram
    autonumber
    participant M as 商家(backend面板)
    participant OS as OrderService
    participant DB as 数据库
    participant U as 用户
    participant EQ as 事件

    M->>OS: deliver(order, itemIds, 快递公司, 单号)
    OS->>DB: BEGIN + 行锁校验(Paid/Preparing/PartiallyShipped→Delivered)
    OS->>OS: 校验所选明细未发货且未全额退款
    OS->>DB: 创建 OrderShipping + 绑定收货地址
    OS->>DB: 明细关联 order_shipping_id
    OS->>OS: 全部发货? Delivered : PartiallyShipped
    OS->>DB: 写 OrderLog(Delivered)
    OS->>EQ: dispatch OrderDelivered / OrderPartiallyShipped(提交后派发)
    OS->>DB: COMMIT

    U->>OS: sign(order)
    OS->>DB: BEGIN + 行锁校验(→Signed)
    OS->>DB: 更新 status, signed_at; 发货记录补 sign_at
    OS->>DB: 写 OrderLog(Signed)
    OS->>EQ: dispatch OrderSigned(提交后派发)
    OS->>DB: COMMIT

    U->>OS: complete(order)
    OS->>DB: BEGIN + 行锁校验(→Completed)
    OS->>DB: 写 OrderLog(Completed)
    OS->>EQ: dispatch OrderCompleted(提交后派发)
    OS->>DB: COMMIT
```

> 发货为「整条订单明细」粒度：已发货明细不可重复发货，已全额退款明细不可再发货。
> 已支付订单全部退款后进入 `Refunded`，不再允许发货/签收。

## 4. 门店自提（pickup）

```mermaid
sequenceDiagram
    autonumber
    participant U as 用户
    participant OS as OrderService
    participant DB as 数据库
    participant M as 门店商家
    participant CMD as 定时任务
    participant EQ as 事件

    Note over U,OS: 支付后状态为 PickupPending, 已生成核销码
    M->>OS: verify(order, pickupCode)
    OS->>DB: BEGIN + 行锁校验(PickupPending→Verified)
    OS->>OS: 校验核销码一致(防并发重复核销)
    OS->>DB: 更新 status, verified_at, verified_by
    OS->>DB: 写 OrderLog(Verified)
    OS->>EQ: dispatch OrderVerified(提交后派发)
    OS->>DB: COMMIT
    OS-->>M: 核销成功(订单转已完成链路)

    Note over CMD,DB: app:mall:pickup-timeout 每日扫描
    CMD->>OS: 超过 N 天未核销(PickupPending)
    OS->>OS: 创建全额仅退款(自动审核) + 提交资金退款
    Note over OS: 财务确认到账后订单转 Refunded
```

超时天数由 `custom.mall.pickup_expired_days` 控制（默认 7 天，`0` 关闭）。

## 5. 取消 / 删除（未支付释放资源）

```mermaid
sequenceDiagram
    autonumber
    participant U as 用户/管理员
    participant OS as OrderService
    participant DB as 数据库
    participant CS as CouponService
    participant EQ as 事件

    U->>OS: cancel(order)
    OS->>DB: BEGIN + 行锁校验(可取消状态)
    OS->>OS: releaseOrderResources
    loop 订单明细 (Ordered 扣库存类型)
        OS->>DB: orderable->restoreStock(qty)
    end
    OS->>CS: releaseFromOrder(幂等释放券)
    OS->>DB: status=Canceled + OrderLog(Canceled)
    OS->>EQ: dispatch OrderCanceled(提交后派发)
    OS->>DB: COMMIT

    U->>OS: delete(order) — 仅 Pending/Canceled
    OS->>DB: BEGIN + 行锁重读订单
    OS->>OS: 锁内再次校验状态
    opt Pending
        OS->>OS: 先释放库存与优惠券
    end
    OS->>DB: 软删订单 + OrderLog(Deleted)
    OS->>DB: COMMIT
```

> 取消/删除只回补「下单已扣减」的库存（`DeductStockType::Ordered`）。
> `DeductStockType::Paid` 的商品此时尚未扣库存，不会重复回补。

## 6. 退款（RefundService + PaymentRefundService）

```mermaid
sequenceDiagram
    autonumber
    participant U as 用户
    participant RS as RefundService
    participant DB as 数据库
    participant M as 商家
    participant F as 财务
    participant PRS as PaymentRefundService
    participant PM as 支付渠道(微信/余额)

    U->>RS: createRefund(order, 类型, 明细)
    RS->>DB: BEGIN + 订单行锁
    RS->>RS: validateOrderForRefund(按履约方式+状态)
    RS->>RS: validateRefundType / validateRefundItems
    RS->>RS: calculateRefundAmount(含运费, 不得超过实付)
    RS->>DB: 创建退款单
    alt 仅退款
        RS->>DB: 自动审核 → Processing
    else 退货退款
        RS->>DB: 等待商家审核
    end
    RS->>DB: COMMIT + RefundInitialized(提交后派发)

    M->>RS: approveRefund / rejectRefund
    opt 需退货
        U->>RS: shipReturn(寄回)
        M->>RS: confirmReceive(确认收货)
        Note over RS: Shipping → Processing(不落库 Received)
    end

    M->>RS: confirmRefund
    RS->>DB: BEGIN + 退款单行锁
    RS->>DB: 幂等检查(已有进行中的支付退款则返回)
    RS->>PRS: createPaymentRefund(待审核)
    RS->>DB: 退款单保持 Processing + 日志
    RS->>DB: COMMIT

    F->>PRS: approve + execute
    PRS->>PM: 原路退回
    alt 到账成功
        PRS->>RS: completeFromPaymentRefund(refund)
        RS->>DB: BEGIN + 退款单行锁
        RS->>DB: status=Completed, refund_at
        RS->>DB: 回补库存(Ordered/Paid 均已扣减)
        alt 全部订单项已退完
            RS->>DB: order.status=Refunded
            RS->>DB: 释放优惠券
        end
        RS->>DB: COMMIT + RefundCompleted(提交后派发)
    else 通道失败(可重试)
        PRS->>RS: logPaymentFailure(仅记录日志)
        Note over RS: 退款单保持 Processing，等待重试
    else 财务驳回/取消
        PRS->>RS: failFromPaymentRefund
        RS->>DB: status=Failed + RefundFailed(提交后派发)
    end
```

**关键点：**

- 售后单 `Completed` 以「资金实际退回」为准，`confirmRefund` 只提交支付退款单，不再提前完成售后。
- 支付退款单完成（微信到账 / 余额退回）后，才回补库存、释放优惠券、写 `RefundCompleted`。
- 部分退款不改变订单状态；全部订单项退完才进入 `Refunded` 终态。
- `RefundStatus::Received` 仅保留枚举兼容，`confirmReceive()` 实际把状态从 `Shipping` 直接推进到 `Processing`。
- `RefundService` 的校验、状态判断与资源回收均在事务内加锁后执行；支付退款单的审核/执行使用条件更新抢占，避免并发重复打款。

## 关键机制说明

| 机制 | 实现 |
|---|---|
| 状态机 | `OrderStatus::canTransitionTo(target, fulfillment_type)`，按履约方式分流；退款完成写 `Refunded` 属旁路状态机（见 order-status.md） |
| 并发防护 | 多数状态流转：`assertCanWithLock`（`lockForUpdate` + 重读校验）；支付/删除订单/删物流按各自事务内行锁校验；退款：`RefundService::lockRefund`；支付单：`PaymentService::markPaidWithBusiness` 行锁 + 幂等；支付退款：条件更新抢占 |
| 拆单原子性 | 外层事务按租户循环建单，任一失败整体回滚（内层为嵌套 savepoint） |
| 库存 | `DeductStockType::Ordered` 下单扣减；`DeductStockType::Paid` 支付成功扣减；取消只回补下单扣减部分；退款完成按退款明细回补 |
| 优惠券 | 下单即核销，取消/删除/全部退款完成时幂等释放 |
| 事件驱动 | 事件实现 `ShouldDispatchAfterCommit`，事务提交后派发；`OrderCreated/Paid/Delivered/Signed/Verified/Completed/Canceled` 与 `RefundInitialized/Completed/Failed` |
| 结算 | `OrderCompleted` 事件已预留，`OrderCompletedListener` 当前为空实现，结算（`ShouldSettlement` / Voucher）尚未接入 |
| 审计 | 每步写 `OrderLog` / `RefundLog`（action + 操作人 + 前后状态 + 上下文） |
