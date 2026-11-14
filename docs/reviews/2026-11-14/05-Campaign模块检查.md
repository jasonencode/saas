# Campaign 模块 检查报告

- **检查日期**：2026-11-14
- **检查范围**：`app/Models/Campaign/**`、`app/Services/Campaign/**`、`app/Http/{Controllers,Requests,Resources}/Campaign/**`、`app/Policies/Campaign/**`、`routes/apis/campaign.php`、Campaign 相关迁移、`app/Filament/**` 中 Campaign 资源/表单/表格/关系管理器、`app/Jobs/Campaign/**`、`tests/Feature/Campaign/**`
- **检查方式**：逐文件走读 + 关键链路（领券 → 核销 / 抽奖 → 兑奖 / 领红包 → 打款）推演 + 旧报告逐条复核
- **结论**：11-07 的提交已修复「抽奖不扣积分」与「红包领取 check-then-act」；但**红包 Job 仍非幂等（重试换单号二次打款）**、**`usage_limit` 仍被当作核销上限（券发满即全部作废）**、**打款仍取用户首个租户的支付配置**。后台三张核心表仍无详情入口，运营能力（发券/兑奖/生成红包码）在 UI 上不可达。

**统计：严重 2 / 高 2 / 中 6（共 10 条）。**

---

## 一、模块概览

| 子域 | 现状 | 主要风险 |
|---|---|---|
| 优惠券 | 发放、领取、下单核销、退款释放链路完整 | `usage_limit` 语义混用，发满即不可用 |
| 抽奖 | 积分扣减已接入事务；奖品库存原子扣减 | 无限次返回 `PHP_INT_MAX`；权重全 0 命中第一个；红包奖品无法兑付 |
| 红包 | 领取条件更新防并发；打款走微信 | Job 非幂等、租户配置取错、金额浮点截断 |
| 后台 | 券/抽奖/红包资源齐全 | 无 `ViewAction`，关系管理器不可达 |

---

## 二、严重问题

### CAMP-01 ｜ 严重 ｜ 资金 / 幂等

**红包 Job 非幂等：重试换新单号二次打款，且多处静默 return**

- **位置**：`app/Jobs/Campaign/SendRedpackJob.php:33-84`（56、58-61、79-83）、`:99-110`
- **代码要点**：`handle()` 不校验当前状态；每次都重新生成 `bill_no` 并落库；`!isActive()`、无 openid、无支付配置三处直接 `return`；`sendRedpack()` 未传 `notify_url`。
- **失败场景**：微信「已受理但超时」→ 置 Failed → 队列重试 → 新单号再打一次，用户收到 2～3 份；无 openid/无商户配置时码停在 `Claimed`，钱没出、无告警。
- **修复建议**：首行状态守卫；`bill_no` 在领取时生成并重试复用（渠道按单号天然幂等）；失败先按单号查单；静默分支写日志 + 置 Failed + 告警；补 `notify_url` 与回调 Job。

### CAMP-02 ｜ 严重 ｜ 优惠券 / 业务

**`usage_limit`（发放量）被当作核销上限：券发满后所有已领券都不能用**

- **位置**：`app/Models/Campaign/Coupon.php:44-56`（51）、`app/Services/Campaign/CouponService.php:34-58,317-359,429-443`、`CouponResource.php:31`
- **代码要点**：`canBeUsed()` = `isValid() && users()->count() < usage_limit`；核销计算必经 `canBeUsed()`，而 `doSendToUser()` 又用同一字段做发放上限。
- **失败场景**：`usage_limit=100` 领满后，第 1 个来核销的用户被拒「优惠券不可用」，100 张券全部作废；`CouponUserResource` 的 `can_be_used` 只看 `isValid()`，前端显示可用、下单必失败。
- **修复建议**：拆分 `issue_limit` 与 `redeem_limit`；`canBeUsed()` 只判定义有效；补「领满仍可核销」回归。

---

## 三、高优先级问题

### CAMP-03 ｜ 高 ｜ 租户 / 资金

**红包打款使用「用户第一个租户」的支付配置，而非活动租户**

- **位置**：`app/Jobs/Campaign/SendRedpackJob.php:99-110`
- **失败场景**：用户先在 A 店下过单，后领 B 店红包 → 用 A 的商户号出款：B 预算不动、A 账对不上；A 余额不足则任务永远失败。
- **修复建议**：`$tenantId = $code->redpack->tenant_id`；取不到配置显式失败并告警。

### CAMP-04 ｜ 高 ｜ 后台可达性

**三张核心表无 `ViewAction`/`recordUrl`，奖品池/红包码/发券/兑奖 UI 不可达**

- **位置**：`app/Filament/Tables/Campaign/LotteriesTable.php:51-57`、`RedpacksTable.php:51-56`、`app/Filament/Tenant/.../Coupons/Tables/CouponsTable.php:68-73`
- **失败场景**：`PrizesRelationManager`、`CodesRelationManager`、`UsersRelationManager`、`PrizeRecordsRelationManager` 全部无入口；后台只能建活动，不能运营。
- **修复建议**：三张表加 `Actions\ViewAction::make()`（或 `recordUrl`）；补「访问 view 路由」冒烟测试。

---

## 四、中优先级问题

### CAMP-05 ｜ 中 ｜ 租户 / 准入

**租户可见性双向缺陷：无 `user_tenant` 的新用户被 404，匿名无租户头却放行全平台**

- **位置**：`CouponController.php:309-333,352-365`、`RedpackController.php:132-153,161-174`、`LotteryController.php:200-246`
- **失败场景**：(a) 未下过单的新用户领券/领红包全 404，`/coupons/my` 恒空；(b) 匿名不带 `X-Tenant-Id` 请求列表 → 返回所有租户活动。
- **修复建议**：准入改为「租户头有效 + 活动属于该租户」，不依赖 `user_tenant`；无租户头匿名一律空集/400。

### CAMP-06 ｜ 中 ｜ 兑奖

**`redpack` 类型奖品无法兑付；批量兑奖静默吞异常**

- **位置**：`LotteryService.php:255-263,388-422`、`PrizesRelationManager.php:68-76`、`PrizeRecordsRelationManager.php:72-78`
- **失败场景**：抽中红包奖品永远 `Pending`；后台批量兑奖 `catch (InvalidArgumentException) {}` 无任何提示。
- **修复建议**：`draw()`/Job 按类型分发，红包复用微信转账；批量兑奖汇总失败原因。

### CAMP-07 ｜ 中 ｜ 金额精度

**红包金额浮点乘 100 截断，迁移注释单位矛盾**

- **位置**：`SendRedpackJob.php:67`、`create_redpacks_table.php:44-46`、`RedpackService.php:121-127`
- **失败场景**：`0.29 * 100 = 28.999…` → 截断成 28 分，用户少收 1 分；注释写「分」但全链路按「元」。
- **修复建议**：全链路以整数分存储，或 `(int) bcmul((string) $amount, '100', 0)`；补精度用例并修正注释。

### CAMP-08 ｜ 中 ｜ 统计

**租户面板统计跨租户混合口径；「已领取」只算 `Claimed`**

- **位置**：`app/Filament/Tenant/Clusters/Campaign/Widgets/CampaignStatsWidget.php:23-34`
- **失败场景**：卡片里「券/红包活动」是本租户，「领券用户/抵扣金额/红包码」是全平台；`Sending/Sent` 的码被算进「待领取」。
- **修复建议**：显式 `whereHas('coupon/redpack', ofTenant)`；已领取改为 `whereIn(['claimed','sending','sent'])`。

### CAMP-09 ｜ 中 ｜ 运营 / 性能

**发券可跨租户选人、可删已核销券；列表与可用券 N+1**

- **位置**：`UsersRelationManager.php:63-97,100-107`、`CouponResource.php:31,41-66`、`CouponController.php:246-257,270`
- **失败场景**：租户 A 可把券发给租户 B 用户；删除已核销券后售后退款无法回查；券列表 20 行 40+ SQL。
- **修复建议**：`searchUsers()` 加租户过滤；已核销禁止删除；列表 `withCount/withExists`，`available()` 批量预取。

### CAMP-10 ｜ 中 ｜ 抽奖规则

**次数/概率/限领口径：无限次返回 `PHP_INT_MAX`、权重全 0 命中第一个、限领把已取消/已过期算入**

- **位置**：`app/Models/Campaign/Lottery.php:100-134`、`LotteryService.php:179-199`、`LotteryPrize.php:89-100`、`LotteryExpireCommand.php:18-24`
- **失败场景**：前端拿到的可用次数是 9223372036854775807；权重全 0 时 100% 中第一个奖品；奖品过期后仍占用户限领名额。
- **修复建议**：无限次返回 `null`/约定值；总权重 ≤0 抛配置错误或只出「谢谢参与」；限领排除 Cancelled/Expired。

---

## 五、旧问题状态（2026-11-01 → 2026-11-14）

| 旧编号 | 状态 | 说明 |
|---|---|---|
| CAMP-01 积分不扣/无限抽 | 有变化 | 已接入 `deductPoints`；`max_draws_per_user=null` 仍返回极大值 |
| CAMP-02 红包 Job 非幂等 | 仍存在 | 重试换单号、静默 return、无 notify_url |
| CAMP-03 usage_limit 当核销上限 | 仍存在 | `Coupon.php:51` |
| CAMP-04 打款取首个租户 | 仍存在 | `SendRedpackJob.php:101` |
| CAMP-05 红包码后台可改 | 部分修复 | 状态收敛、编辑/删除受限；Active 码金额仍可改 |
| CAMP-06 无 user_tenant 不能领券 | 仍存在 | 测试把 404 固化为预期 |
| CAMP-07 虚拟奖品永远 pending | 部分修复 | 余额/积分/券已自动兑付；redpack 仍 pending |
| CAMP-08 无 ViewAction | 仍存在 | 三张表仍只有 Edit/Delete |
| CAMP-09 奖品总量不联动 | **已修复** | `creating/updating` 同步剩余量 |
| CAMP-10 红包领取竞态 | **已修复** | 条件更新 + 影响行数判断 |
| CAMP-11 浮点 ×100 | 仍存在 | 见 CAMP-07 |
| CAMP-12 统计跨租户 | 仍存在 | 见 CAMP-08 |
| CAMP-13/14 券 N+1/跨租户选人 | 仍存在 | 见 CAMP-09 |
| CAMP-15 批量红包码逐条插入 | 仍存在 | 单事务 5 万条 |
| CAMP-16 限领含取消/权重全 0 | 仍存在 | 见 CAMP-10 |
| CAMP-17 折扣值无上限 | 仍存在 | `CouponForm` 无 `maxValue(100)` |
| CAMP-18 测试缺口 | 有变化 | 已补积分/兑付/并发；仍缺「发满后核销」「Job 幂等」 |

---

## 六、修复优先级

1. **P0**：CAMP-01（重复打款）、CAMP-02（券发满作废）。
2. **P1**：CAMP-03（打错商户）、CAMP-04（运营不可达）。
3. **P2**：CAMP-05/06/07（准入、兑付、精度）。
4. **P3**：CAMP-08/09/10（统计、性能、规则）。
