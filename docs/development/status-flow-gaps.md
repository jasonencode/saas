# 状态流转实现缺口清单

> 汇总自 `docs/flows/` 各篇流程文档的「实现缺口」小节（梳理日期 2026-09-11），用于排期修复。每条都标注了精确的代码位置与对应的流程文档；优先级为**按资金安全与链路闭环程度的主观判断**，不是既有约定。

**判定口径：** 「无写入方」指该状态在 `app/` 下不存在任何写入点（grep 零命中）；「静默停滞」指代码在异常 / 前置条件不满足时 `return`，记录永远停留在中间态。

---

## 一、P0 — 资金链路断裂（已修复）

| 模块 | 缺口 | 影响 | 位置 | 详见 |
|------|------|------|------|------|
| ~~充值~~ | ✅ **已修复（2026-09-11）** 支付成功后由 `PaymentService::markPaidWithBusiness()` 统一推进：回调命中 `RechargeOrder` 时连续执行 `markPaid()` + `complete()`，余额 / 积分入账 | 充值链路闭环可用 | `app/Services/Finance/PaymentService.php`、`app/Http/Controllers/Finance/PaymentController.php` | [recharge-status.md](../flows/recharge-status.md) |
| ~~支付~~ | ✅ **已修复（2026-09-11）** 微信回调不再只改支付单：与余额支付共用同一出口，回调成功即推进商城订单 / 充值单；重复投递以「支付单已 Paid」为幂等键 | 微信付款成功后订单自动前进，不再需人工干预 | 同上 | [payment-status.md](../flows/payment-status.md) |

---

## 二、P1 — 链路可跑通但存在资金 / 风控风险

| 模块 | 缺口 | 影响 | 位置 | 详见 |
|------|------|------|------|------|
| ~~提现~~ | ✅ **已修复（2026-09-11）** 状态前置校验移入事务并加行锁，账户行同样加锁；创建提现单的余额校验也移入锁内 | 重复审核 / 重复打款不再可能重复解冻或重复扣减 | `app/Services/Finance/WithdrawService.php` | [withdraw-status.md](../flows/withdraw-status.md) |
| ~~支付退款单~~ | ✅ **已修复（2026-09-11）** 新增 `PaymentRefundService`：申请 → 财务审核（Approved / Rejected）→ 执行原路退回（微信 v3 退款 / 余额退回用户余额）→ 失败可重试；后台与租户的退款订单列表、详情页已挂审核 / 驳回 / 执行 / 重试动作；售后单 `confirmRefund()` 会自动生成待审核的支付退款单 | 退款资金真正退回，且申请中也会占用可退额度 | `app/Services/Finance/PaymentRefundService.php`、`app/Filament/Actions/Finance/*PaymentRefundAction.php` | [payment-refund-status.md](../flows/payment-refund-status.md) |
| 结算凭据 | `VoucherService::create()` 无任何调用方；后台手建凭据走 Filament 直建模型，**不派发 `VoucherAutoRunJob`**，且表单无 `status` 字段 | 结算链路没有业务入口；手建凭据永久停在 `Pending`，界面上也无法手工补执行 | `app/Services/Finance/VoucherService.php`、`app/Filament/Backend/.../Vouchers/Pages/ManageVouchers.php:16` | [voucher-status.md](../flows/voucher-status.md) |
| 抽奖奖品 | 余额 / 积分 / 红包三类奖品**无兑付路径**（`fulfillPrize()` 对非 Coupon / Physical 直接抛错） | 中奖记录永久停留在待兑奖，用户实际收不到奖励 | `app/Services/Campaign/LotteryService.php:257` | [lottery-prize-status.md](../flows/lottery-prize-status.md) |
| 红包码 | 发放 Job 的四项前置检查不满足时**静默 `return`**（无 openid / 无商户配置 / 活动失效 / 无用户） | 码永久停在「已领取」，无告警、无重发入口，用户以为领到了 | `app/Jobs/Campaign/SendRedpackJob.php:35-54` | [redpack-code-status.md](../flows/redpack-code-status.md) |
| 红包码 | 领取的「可领取」判断与状态更新之间无锁 / 无条件更新 | 并发领取可能让同一个码被多个用户占用 | `app/Services/Campaign/RedpackService.php:114-123` | 同上 |
| 商品 | `Rejected → Up` 绕过审核（`up()` 允许从被驳回直接上架） | 审核约束可被绕过，违规商品整改前即可上架 | `app/Services/Mall/ProductService.php:61` | [product-status.md](../flows/product-status.md) |

---

## 三、P2 — 体验 / 一致性 / 可运维性

### 3.1 财务

| 缺口 | 影响 | 位置 | 详见 |
|------|------|------|------|
| 支付单 `Processing` / `Failed` / `Canceled` / `Refunded` 无写入方 | 网关异常、异步支付、超时关闭均无状态落点 | - | [payment-status.md](../flows/payment-status.md) |
| 无过期关闭任务（支付单 / 充值单 / 提现单） | `expired_at` 只做前置校验；超时单据长期滞留，提现单还会一直占用冻结金额 | `routes/console.php` 无对应调度 | 各自流程文档 |
| 充值 `markFailed()` 无调用方，`Processing` / `Canceled` 无写入方 | 支付失败场景无落点 | `app/Services/Finance/RechargeService.php:143` | [recharge-status.md](../flows/recharge-status.md) |
| 提现 `Processing` 无写入方 | 通道打款无中间态（实现是审核通过直接人工打款） | `WithdrawOrderStatus` | [withdraw-status.md](../flows/withdraw-status.md) |
| 发票 `InvoiceStatus::Sent` 无写入方 | 没有发送发票 / 邮件通知的实现 | - | [invoice-status.md](../flows/invoice-status.md) |
| 发票申请无撤销 / 无作废红冲 / 无重新开票 | 开错无法纠正，用户提交后无法撤回 | `app/Http/Controllers/User/InvoiceController.php` | 同上 |
| 发票并发重复申请 | 仅应用层查重（无数据库唯一约束），并发可能产生重复申请 | `InvoiceService::validateOrders():155` | 同上 |
| 后端发票列表页缺「驳回」动作 | 驳回必须进详情页 | `app/Filament/Backend/.../InvoiceApplications/Tables/InvoiceApplicationsTable.php:52` | 同上 |
| 结算凭据无重跑入口、表单无 `status` | `Failure` 重试只能靠代码调用 | `app/Filament/Backend/.../Vouchers/` | [voucher-status.md](../flows/voucher-status.md) |

### 3.2 订单与售后

| 缺口 | 影响 | 位置 | 详见 |
|------|------|------|------|
| 退货物流 `Pending` / `Checked` / `Rejected` 不可达 | 无「验收」「拒收」动作，商家只能签收 | `app/Enums/Mall/RefundExpressStatus.php` | [refund-status.md](../flows/refund-status.md) 第六节 |
| 物流记录 `hasOne` + `updateOrCreate` | 重复提交覆盖原记录，旧物流单号不留痕 | `app/Services/Mall/RefundService.php:618` | 同上 |

### 3.3 活动与内容

| 缺口 | 影响 | 位置 | 详见 |
|------|------|------|------|
| 抽奖 `Expired` 无写入方，兑奖无有效期 | 待兑奖记录无限累积 | - | [lottery-prize-status.md](../flows/lottery-prize-status.md) |
| 抽奖取消奖品无界面入口 | 运营无法在后台取消问题奖品 | 中奖记录表无取消动作 | 同上 |
| 抽奖兑奖无幂等保护（校验在事务外、无行锁） | 并发批量兑奖可能重复发券 | `LotteryService::fulfillPrize():261` | 同上 |
| 红包码编辑表单可任意改状态 | 可把 `Sent` 改回 `Active` 造成重复发放 | `CodesRelationManager::form()` | [redpack-code-status.md](../flows/redpack-code-status.md) |
| 优惠券**部分退款不返还** | 只有全额退款才返还券，部分退款时券保持已使用 | `app/Services/Mall/RefundService.php:742` 的调用条件 | [coupon-status.md](../flows/coupon-status.md) |
| 优惠券停用 / 过期无通知 | 持有用户不知情 | 无事件派发 | 同上 |
| 意见反馈服务层与控制器校验不一致 | `appendUserMessage()` 不拦 `Closed`，绕过控制器的调用可让已关闭反馈「复活」 | `SuggestService.php:50` vs `SuggestController.php:103` | [suggest-status.md](../flows/suggest-status.md) |
| 意见反馈无通知 / 无自动关闭 | 双方互动只能靠刷新查看 | 无事件派发 | 同上 |

### 3.4 用户与商城

| 缺口 | 影响 | 位置 | 详见 |
|------|------|------|------|
| 实名认证审批动作无状态前置校验 | 服务层可重复审核，新增入口易漏校验 | `RealnameService::approve()` / `reject()` | [realname-status.md](../flows/realname-status.md) |
| `UserRealnameApproved` / `UserRealnameRejected` 无监听者 | 认证通过不联动发身份 / 通知 | `app/Events/User/` | 同上 |
| 实名认证 `Approved` 后不可变更 / 撤销 | 资料变更只能走人工改库 | `RealnameService::submit():38` | 同上 |
| 开店申请审核动作无状态校验、并发可能两条 `Pending` | 重复审核 / 重复申请 | `StoreService::auditApply()`、`createApply()` | [store-apply-status.md](../flows/store-apply-status.md) |
| 开店申请审核结果无通知、无撤回 | 租户只能自己刷新页面看结果 | `StoreService::auditApply()` | 同上 |
| 商品无自动下架、无下架原因、SKU 无状态 | 库存归零 / 活动结束需人工下架；无法按规格下架 | `app/Services/Mall/ProductService.php:77` | [product-status.md](../flows/product-status.md) |

---

## 四、文档漂移（改动代码前先对齐）

| 文档 | 问题 | 正确事实 |
|------|------|----------|
| `docs/apis/finance.md:533` | 提现流转写成 `approved → processing → completed` | `Processing` 从未被写入，实际是 `Approved → Completed`（见 [withdraw-status.md](../flows/withdraw-status.md)） |
| `docs/apis/finance.md`（充值章节） | 列出了「取消充值订单」接口 | 路由不存在，`routes/apis/finance.php:48-58` 只有列表 / 创建 / 详情 |
| `docs/apis/finance.md`（充值章节） | 未说明充值到账依赖支付成功回写 | 当前无回写路径，见 P0 第一条 |
| `docs/apis/finance.md`（发票章节） | 未说明 `Sent` 状态不可达 | 见 [invoice-status.md](../flows/invoice-status.md) |

---

## 五、修复时的通用注意

1. **「无写入方」的状态不要直接删枚举** —— 多数是设计意图明确、只差接线（如支付退款单的审核链路、发票的 `Sent`），删掉会丢失后续实现的状态位。
2. **补写入口时同步补状态校验** —— 提现、实名认证、开店申请、抽奖兑奖这几处的服务方法都缺少前置状态校验，目前只靠 Filament 动作的 `visible` 兜底。
3. **并发场景优先做原子化** —— 已具备原子保护的是优惠券核销（乐观 CAS）、结算凭据执行（CAS + `Success` 拦截）、提现（事务内行锁，2026-09-11 加固）；待补的是红包码领取、抽奖兑奖。
4. **补缺状态后回到流程文档同步** —— `docs/flows/` 各篇的「实现缺口」小节与本文档需要一起更新。

---

## 附：修复记录

| 日期 | 修复内容 | 验证 |
|------|----------|------|
| 2026-09-11 | 提现并发加固：`WithdrawService` 的 `create()` / `review()` / `complete()` / `cancel()` 全部改为「事务内 `lockForUpdate` 读订单 + 读账户」，状态判断以锁内实例为准 | `tests/Feature/Finance/WithdrawServiceTest.php` 26 用例通过（含新增的重复审核、重复打款、审核后取消、余额已被冻结 4 个并发语义用例） |
| 2026-09-11 | 修复 `WithdrawServiceTest` 存量失败：原文件向 `create()` 传了不存在的 `tenantId` 具名参数（`WithdrawOrder` 无租户列），且直接建单后断言冻结额，与实际签名/语义不符 | 修复前 13 失败 / 8 通过 → 修复后 26 通过 |
| 2026-09-11 | 修复 P1「支付退款单只到待审核」：新增 `PaymentRefundService`（申请 / 审核 / 驳回 / 取消 / 执行原路退回 / 重试）、`WechatPaymentService::refund()`（微信 v3 退款）、`payment_refunds` 补 `failed_reason` / `channel_refund_no` / `source` 多态并放开 `created_by` 为可空；商城 `RefundService::confirmRefund()` 自动生成待审核退款单；后台与租户面板挂 4 个动作 + `PaymentRefundPolicy` 4 个按钮权限 | 新增 `tests/Feature/Finance/PaymentRefundServiceTest.php` 14 用例（额度占用、审核、微信成功/失败/重试、余额退回、通道不支持、售后单打通）；`CouponRefundTest` 补支付单夹具后 11 用例通过 |
| 2026-09-11 | 修复两条 P0：新增 `PaymentService::markPaidWithBusiness()` 作为「标记支付单已支付 + 推进关联业务」的统一出口，余额支付与微信回调共用；回调侧包事务、失败回滚并返回 `FAIL` 让微信重试；以支付单 `Paid` 状态做重复回调幂等 | 新增 `tests/Feature/Finance/PaymentServiceTest.php` 9 用例（充值到账、商城订单推进、余额支付、幂等、回调不匹配单号），`tests/Feature/Finance` 58 用例全绿 |

**同批发现的无关存量失败（未在本主题内处理）：** `Tests\Unit\Enums\Campaign\RedpackCodeStatusTest`、`Tests\Unit\Enums\Mall\RefundStatusTest`（label / color 期望值过期）、`Tests\Unit\Mall\OrderAmountTest`（金额期望为字符串 `'13.00'`，实际返回 `13.0`），共 5 例。

> 补充：全量 `php artisan test` 会在 `Tests\Feature\Auth\LoginApiTest` 因验证码图片解码耗尽 128M 内存而中断（`intervention/image`），需提高 `memory_limit` 或改用 `vendor/bin/phpunit` 分目录跑。
