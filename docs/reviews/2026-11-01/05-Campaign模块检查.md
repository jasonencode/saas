# Campaign 模块 检查报告

- **检查日期**：2026-11-01
- **检查范围**：`app/Models/Campaign/*`、`app/Services/Campaign/*`、Campaign 的 Controller / Request / Resource、`app/Jobs/Campaign/*`、`app/Policies/Campaign/*`、`app/Filament/Tenant/Clusters/Campaign/**`、`routes/apis/campaign.php`、Campaign 相关迁移
- **检查方式**：逐文件静态走读 + 关键链路推演 + 定点测试核对（`LotteryApiTest` 全量运行 1 条失败已归因）
- **结论**：**优惠券的金额计算（bcmath、分摊尾差、乐观核销、预览/核销同源）做得相当扎实**，但抽奖与红包两条资金/库存链路存在**严重缺陷**：积分不扣、抽奖无限次、红包重复打款、券发放量被误当核销量。另有一个后台可达性问题，使奖品池、红包码、兑奖、发券等核心运营动作从 UI 不可达，这也是缺陷长期未被发现的原因。

**统计：严重 3 / 高 5 / 中 8 / 低 2（共 18 条）。**

---

## 一、模块概览

| 子域 | 主要文件 | 现状判断 |
|---|---|---|
| 优惠券 | `CouponService`（479 行，事实核心）、`Coupon`、`CouponController` | 金额计算健壮；发放/核销语义混乱、租户入口判定过严、列表 N+1 |
| 抽奖 | `LotteryService`（346 行）、`Lottery`、`LotteryPrize` | 库存扣减原子；次数/积分/奖品类型三条链路都断了 |
| 红包 | `RedpackService`、`SendRedpackJob` | 领取非原子、打款非幂等、后台可改状态，资金风险最高 |
| 后台 | Campaign 3 个资源 + 统计 Widget | 表单/表格统一，但详情页不可达、统计跨租户 |

**路由与测试**：`routes/apis/campaign.php` 的券/抽奖/红包写操作均挂 `auth:sanctum`，抽奖/领红包额外挂 `lock:` 中间件；`tests/Feature/Campaign` 有 5 个测试文件、30+ 用例，但关键资金路径（红包 claim、积分扣减、券发放满额后核销）没有有效断言。

---

## 二、严重问题

### CAMP-01 ｜ 严重 ｜ 逻辑 / 资金与库存
**积分抽奖只记录消耗、从不扣积分；`max_draws_per_user` 为空时返回 `PHP_INT_MAX`**

- **位置**：`app/Services/Campaign/LotteryService.php:63-92`、`app/Models/Campaign/Lottery.php:132-133`
- **代码**：
  ```php
  // 计算消耗金额
  $costAmount = $costType === 'points' ? $lottery->points_per_draw : 0;

  // 创建抽奖记录（只写流水，没有扣积分）
  $draw = LotteryDraw::create([... 'draw_cost_amount' => $costAmount]);
  ```
  ```php
  // 积分模式：不限次数
  return $totalRemaining ?? PHP_INT_MAX;
  ```
- **失败场景**：`draw_mode=points` 时只把 `points_per_draw` 写进流水，未调用 `UserAccountService::modifyAsset(..., Points, -N)`，用户积分不变；活动未配 `max_draws_per_user`（表单允许留空）时次数检查永远通过。任何登录用户可无限免费抽奖直到奖品 `remaining_quantity` 归零，`lottery_draws` 被灌爆。
- **验证**：`tests/Feature/Campaign/LotteryServiceTest::test_draw_with_points_mode_deducts_points` 只断言流水的 `draw_cost_type/amount`，名字与行为不符，掩盖了缺陷。
- **建议**：事务内、`LotteryDraw::create` 之前用 `UserAccountService::modifyAsset()` 扣积分（内部有行锁与余额校验），失败回滚；无 `max_draws_per_user` 时按「余额 / 单次消耗」推导上限；补「余额不足 → 400 且无流水」测试。

### CAMP-02 ｜ 严重 ｜ 幂等 / 资金
**红包任务重试即生成新 `bill_no` 重新打款，无状态前置校验、无回调**

- **位置**：`app/Jobs/Campaign/SendRedpackJob.php:22-26`、`:56-83`
- **代码**：
  ```php
  #[Tries(3)]
  class SendRedpackJob extends BaseJob
  ...
  $billNo = 'RP'.date('YmdHis').Str::random(6);
  $this->code->update(['bill_no' => $billNo, 'status' => RedpackCodeStatus::Sending]);
  ...
  } catch (\Throwable $e) {
      $this->code->update(['status' => RedpackCodeStatus::Failed]);
      throw $e;
  }
  ```
- **失败场景**：`handle()` 不检查当前状态（`Sending`/`Sent` 也会继续），每次执行生成**新单号**。微信「接口超时但实际已受理」是常见情形：第一次已出款 → 超时抛异常 → 置 `Failed` → 队列重试 → 用新单号再打一次 → 用户收到 2~3 份红包。反过来，`openid`/支付配置为空时函数**静默 return**，状态永远停在 `Claimed`，用户没收到钱也无告警；`sendRedpack()` 未传 `notify_url`，异步结果无人接收。
- **建议**：首行校验状态（仅 `Claimed/Failed` 继续）；`bill_no` 在领取时生成并落库、重试复用（微信按单号天然幂等）；失败后先按单号查单；补 `notify_url` 与回调 Job；静默分支写日志 + 置失败并告警。

### CAMP-03 ｜ 严重 ｜ 逻辑（发放量 vs 核销量）
**`Coupon::canBeUsed()` 把「发放总量」当「核销上限」，券发满后所有已领券都不能用**

- **位置**：`app/Models/Campaign/Coupon.php:44-56`、`CouponService.php:40-42`、`create_coupons_table.php:36-41`
- **代码**：
  ```php
  public function canBeUsed(): bool
  {
      if (!$this->isValid()) { return false; }
      // 总发放量已达上限
      if ($this->usage_limit !== null && $this->users()->count() >= $this->usage_limit) {
          return false;
      }
      return true;
  }
  ```
  ```php
  // CouponService::apply()
  if (!$coupon->canBeUsed()) {
      throw new InvalidArgumentException('优惠券不可用');
  }
  ```
  同时 `CouponService::doSendToUser()` 也用同一字段做发放上限（`$issuedCount >= usage_limit` 拒绝发放）。
- **失败场景**：`usage_limit=100` 的券被领满 100 张后，`users()->count()==100`，**第 1 个来核销的用户就被拒绝**，100 张券全部作废；`can_be_used` 字段在「我的券」里来自 `isValid()`，显示「可用」但下单失败，前后端契约不一致。
- **建议**：拆成 `issue_limit`（发放）与 `redeem_limit`（核销）；`canBeUsed()` 只判断定义有效 + 未超核销总量（统计 `coupon_order` 或 `is_used=true`）；补「领满后仍可核销」回归测试。

---

## 三、高优先级问题

### CAMP-04 ｜ 高 ｜ 安全 / 资金（跨租户）
**红包打款取「用户第一个租户」的支付配置，而非活动租户**

- **位置**：`app/Jobs/Campaign/SendRedpackJob.php:92-110`
- **代码**：
  ```php
  $tenantId = $user->tenants()->first()?->getKey();
  ...
  return WechatPayment::ofEnabled()->where('tenant_id', $tenantId)->first();
  ```
- **失败场景**：多租户用户（先买过 A 店、后领 B 店红包）会用 A 的商户号给 B 的活动出款：B 的活动预算不动、A 的账对不上；A 商户号余额不足时任务永远失败。结合 CAMP-10（领取不校验租户），还可能给不属于该租户的用户打款。
- **建议**：`$tenantId = $redpack->tenant_id`；支付配置为空时显式抛错/告警，不要静默 return。

### CAMP-05 ｜ 高 ｜ 安全 / 资金（后台可篡改）
**红包码是资金凭证，后台却允许编辑金额与状态回退**

- **位置**：`app/Filament/Tenant/Clusters/Campaign/Resources/Redpacks/RelationManagers/CodesRelationManager.php:28-43`、`:81-86`
- **代码**：
  ```php
  Forms\Components\TextInput::make('amount')->label('金额')->required()->numeric(),
  Forms\Components\Select::make('status')->options(RedpackCodeStatus::class)->required(),
  ...
  Actions\EditAction::make(),
  Actions\DeleteAction::make(),
  ```
- **失败场景**：把 `Sent` 的码改回 `active`，同一串码可再次领取 → 触发第二个 `SendRedpackJob` → 二次打款；把 `amount` 从 1.88 改成 100 再重试，直接放大出款；删除（软删）后 `bill_no` 唯一约束失效，可重复生成同号。均无二次确认/审计。
- **建议**：只允许 `active → disabled`，禁止编辑金额与状态回退；`EditAction` 换受限 `DisableAction`，加 `requiresConfirmation()` 与审计日志；`Sent` 状态禁止任何修改。

### CAMP-06 ｜ 高 ｜ 授权模型 / 功能阻断
**新用户没有 `user_tenant` 关联时，领券/我的券/可用券全部 404 或空**

- **位置**：`app/Http/Controllers/Campaign/CouponController.php:309-333`、`:352-365`、`app/Services/Mall/OrderActions/CreateOrder.php:163`
- **代码**：
  ```php
  if ($user && !$this->tenantIdsFor($request)->contains($tenant->getKey())) {
      return false;
  }
  ...
  return $this->authorizedTenantIds = $user->tenants()->pluck('tenants.id');
  ```
- **失败场景**：`user_tenant` 关联只在**下单时**由 `CreateOrder` 写入（`syncWithoutDetaching`），注册/登录不写。任何尚未下过单的新用户：领券 → 404「优惠券不存在或已失效」；`/coupons/my`、`/stats`、`/available` → `whereIn('tenant_id', [])` 恒空。「先领券再下单」对新用户完全不可用。现有测试手动 `$user->tenants()->attach($tenant)`，把这个坑掩盖了。
- **建议**：领券/查券的租户判定改为「`TenantResolver` 有效 + 券属于该租户」，不依赖 `user_tenant`；或在领券成功/注册/首次进入租户时补关联。补「无 user_tenant 用户可领券」测试。

### CAMP-07 ｜ 高 ｜ 功能缺陷（奖品无法兑付）
**`balance/points/redpack` 类型奖品的兑付逻辑未实现，永远 pending**

- **位置**：`app/Services/Campaign/LotteryService.php:248-270`、`PrizesRelationManager.php:71-94`
- **代码**：
  ```php
  if ($record->type !== LotteryPrizeType::Physical) {
      throw new InvalidArgumentException('仅实物奖品需要兑奖');
  }
  ```
- **失败场景**：`LotteryPrizeType` 声明了 `Balance/Points/Coupon/Redpack/Physical/None`，但只实现了 `Coupon`（手工触发）与 `Physical`（改状态）。中奖为 `balance/points/redpack` 时生成 `pending` 记录，后台批量兑奖对非实物直接抛「仅实物奖品需要兑奖」（RelationManager 还会 catch 后静默跳过），用户永远拿不到钱/积分/红包，`pending` 永久堆积；抽奖表单却为这三类提供了 `prize_config.amount` 输入框，「能配不能发」。
- **建议**：在 `draw()` 事务内或独立 `DispatchPrizeJob` 按类型分发：`balance/points` → `UserAccountService::modifyAsset`；`redpack` → 复用红包码/转账；`coupon` 自动发放；仅 `physical` 保留人工兑奖。

### CAMP-08 ｜ 高 ｜ 可用性（核心页面不可达）
**三个 Campaign 资源都没有 `ViewAction`/`recordUrl`，本应是运营核心的详情页从 UI 不可达**

- **位置**：`CouponsTable.php:68-73`、`RedpacksTable.php:41-49`、`LotteriesTable.php:36-44`、`CouponResource.php:50-65`
- **代码**：
  ```php
  ->recordActions([
      Actions\ActionGroup::make([
          Actions\EditAction::make(),
          Actions\DeleteAction::make(),
      ]),
  ])
  ```
- **失败场景**：三个资源都注册了 `view` 页与 `getRelations()`，但表格没有 `ViewAction`、也没有 `recordUrl`，`ManageRecords` 页自身不渲染 RelationManager。结果：
  - 抽奖「奖品池」无法进入 → **无法创建奖品**，活动建了也没奖品；
  - 红包码「批量创建」挂在 `CodesRelationManager` 上 → **无法生成红包码**；
  - 优惠券的持有用户/适用商品/订单不可达 → 无法发券、无法核对用券订单；
  - 中奖记录批量兑奖不可达 → 实物奖品无法履约。
- **建议**：三张表补 `Actions\ViewAction::make()`（或 `recordUrl(fn ($record) => static::getUrl('view', ['record' => $record]))`）；补 Filament 冒烟测试访问 `view` 路由。

---

## 四、中优先级问题

| 编号 | 问题 | 位置 | 说明与建议 |
|---|---|---|---|
| CAMP-09 | 奖品总量/剩余量各自独立，无联动 | `PrizesRelationManager.php:49-59`、`LotteryPrize.php:42-50` | 填「总量 100、剩余默认 0」→ 永远不可中奖，列表却把 0 显示成「无限」。新建时 `remaining = total`，表单隐藏剩余量，表格改为「剩余/总量」。 |
| CAMP-10 | 红包领取是 check-then-act，仅靠路由锁 | `RedpackService.php:105-125`、`routes/apis/campaign.php:51-53` | 并发请求都通过 `isClaimable()` 后互相覆盖，两个用户都「领取成功」并各触发打款。改条件更新 `where('status',Active)->whereNull('user_id')` 并判断影响行数；服务层再套锁；补租户校验。 |
| CAMP-11 | 红包金额用浮点乘 100 转分，存在 1 分误差 | `SendRedpackJob.php:67`、`create_redpacks_table.php:44-46` | `0.29 * 100` 截断为 28 分；迁移注释写「分」而代码存「元」。统一以分为单位，或 `(int) bcmul((string)$amount,'100',0)`；补 0.29/1.15/2.29 精度测试。 |
| CAMP-12 | Campaign 统计跨租户混合口径 | `CampaignStatsWidget.php:23-34`、`:73-74` | `CouponUser/CouponOrder/RedpackCode` 不是资源模型，没有租户作用域；同一卡片里「券总数」是本租户、「领券用户/抵扣金额/红包码」是全平台。改为显式 `whereHas('coupon', tenant)`；「已领取」统计应含 `sending/sent`。 |
| CAMP-13 | 优惠券列表/可用券/领券接口 N+1 | `CouponResource.php:43-73`、`CouponController.php:246-270` | 每行 `users()->count()` + `users()->first()`；可用券再逐张查 `coupon_product`。改 `withCount`/`withExists` + 一次 `whereIn` 分组。 |
| CAMP-14 | 发券 RelationManager 可跨租户选用户，且可删除已核销记录 | `UsersRelationManager.php:63-107` | `User::select(...)->where('username','like',...)` 无租户过滤；`DetachAction/DeleteBulkAction` 直接删 `coupon_user`，`coupon_order.coupon_user_id` 变孤儿，退款时无法回查。搜索加租户过滤；删除改「作废 + 审计」。 |
| CAMP-15 | 批量生成红包码在同步请求里逐条查重+插入 | `RedpackService.php:42-57`、`CreateCodeBulkAction.php:34-40` | 最多 5 万码 = 10 万次 SQL，30s 必然超时；活动无总预算约束（5 万 × 3.88 = 19.4 万）。改分批 `insert()`、大队列化、活动加 `total_budget` 校验。 |
| CAMP-16 | 中奖限领把取消记录也算入；总权重为 0 时兜底第一个奖品 | `LotteryPrize.php:59-70`、`LotteryService.php:169-189` | 取消一次奖品后用户被永久挡住；权重全 0 时 100% 中头奖。统计排除 `Cancelled/Expired`；总权重 ≤0 返回「谢谢参与」或抛配置错误。 |

---

## 五、低优先级问题

| 编号 | 问题 | 位置 | 说明 |
|---|---|---|---|
| CAMP-17 | 券折扣值无上限；起止时间字段显隐后残留 | `CouponForm.php:41-45`、`:94-113` | `type=percent` 可存 150%，计算时静默 clamp；`start_at/end_at` 同时 `nullable()->required()` 且按类型显隐，切换后旧值残留。加 `maxValue(100)`、隐藏字段显式 `dehydrated(false)`。 |
| CAMP-18 | 红包/抽奖关键路径测试缺失 | `tests/Feature/Campaign/*` | `RedpackServiceTest` 无 `claim()`；`LotteryServiceTest` 的积分用例只断言流水；`CouponServiceTest` 无「发放满额后核销」。先写失败用例再修。 |

---

## 六、修复顺序

1. **先封资金口子（当天）**：CAMP-02（幂等）、CAMP-04（租户支付配置）、CAMP-05（禁止改码）。
2. **再修业务停摆点（本周）**：CAMP-03（券发满不可用）、CAMP-06（新用户不能领券）、CAMP-01（积分不扣 + 无限抽）。
3. **补后台可达性（本周）**：CAMP-08 一个 `ViewAction` 即可恢复奖品池、红包码、兑奖、发券四个能力。
4. **统一租户口径**：CONT-01、CAMP-12 与 `CouponController::effectiveTenantIds()` 抽成统一 Service/中间件。
5. **补测试**：红包 claim/job、积分扣减、券发放满额后核销、跨租户列表。
