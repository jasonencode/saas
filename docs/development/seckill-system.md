# 秒杀系统开发方案

> 面向多租户商城的秒杀（限时抢购）功能。采用「活动 → 场次」两层结构：活动是营销容器，场次绑定单个 SKU 与时间段，每个场次独立定价、独立名额、独立限购。方案复用现有订单链路（`OrderService` / `OrderItemDto` / `Sku::deductStock`），新增 Redis 名额预扣与回补机制。

## 一、目标与范围

### 1.1 目标

| 目标 | 说明 |
|------|------|
| 限时 | 场次有明确开始/结束时间，未开始、已结束不可购买 |
| 限量 | 场次名额独立于 SKU 总库存，售罄即止 |
| 限购 | 每人每场最多购买 N 件 |
| 防超卖 | 高并发下不超卖、不超名额 |
| 可回补 | 取消、超时、退款按幂等规则回补名额 |
| 多租户 | 所有数据按 `tenant_id` 隔离，平台运营端可代管 |

### 1.2 MVP 范围

- 活动 + 场次管理（Filament 双面板）
- 场次绑定单个 SKU、秒杀价、名额、限购
- 前台场次列表/详情（进行中 + 预告）
- 秒杀下单（不经过购物车）
- 取消/超时/退款回补
- 后台数据查看（已售、剩余、售罄时间）

### 1.3 非目标（后续迭代）

- 一个场次多 SKU（会场）
- 预约提醒、开场排队
- 购物车支持秒杀商品
- 分布式多机房库存

---

## 二、术语与结构

```text
seckill_activities   活动：营销容器，负责名称、描述、上下架
  └── seckill_sessions   场次：一个 SKU + 一个时间窗 + 秒杀价 + 名额 + 限购
        └── seckill_records   资格/成交记录：限购统计、幂等、回补依据
```

示例：

```text
活动：双十一秒杀
├── 场次 10:00：商品 A / SKU A1 / 秒杀价 99 / 名额 100 / 限购 2 / 10:00-12:00
├── 场次 14:00：商品 B / SKU B1 / 秒杀价 199 / 名额 50 / 限购 1 / 14:00-16:00
└── 场次 20:00：商品 C / SKU C1 / 秒杀价 59 / 名额 200 / 限购 3 / 20:00-次日 00:00
```

**选择 SKU 而不是 Product**：价格与库存都在 `skus` 表，一个商品可能有多个规格；后台交互为「先选商品，再选规格」，单规格自动带出。

---

## 三、业务规则

### 3.1 状态机

活动状态（`SeckillActivityStatus`）：

| 状态 | 含义 | 可操作 |
|------|------|--------|
| `draft` | 草稿，前台不可见 | 编辑、添加场次 |
| `published` | 已发布，前台可见场次 | 下架、编辑场次 |
| `closed` | 已结束/手动关闭 | 只读 |

场次状态（`SeckillSessionStatus`）：

| 状态 | 含义 |
|------|------|
| `scheduled` | 待开始 |
| `running` | 进行中 |
| `ended` | 已结束（到点或售罄） |
| `cancelled` | 已取消（人工下线） |

状态由后台手动 + 定时任务共同维护；**下单时以服务端时间为准实时校验**，不依赖状态字段是否及时刷新。

### 3.2 时间与价格

- 场次时间必须 `start_at < end_at`，推荐整点开场
- 同一 SKU 的时间窗不能重叠（应用层校验 + 索引辅助）
- 秒杀价必须 `< sku.price`，且 `>= 0.01`
- 秒杀价不参与会员折扣与优惠券计算：**秒杀价即成交价**，购物车/结算预览的优惠逻辑对秒杀商品跳过
- 秒杀商品单独下单，不与其他商品混单、不使用优惠券

### 3.3 名额与限购

- `seckill_sessions.stock`：本场名额，发布时校验 `stock <= sku.stock`
- `seckill_sessions.sold`：已售数量（冗余计数，便于列表展示与对账）
- 真实库存仍通过 `Sku::deductStock()` 扣减，保证与普通订单共享库存时不超卖
- 限购按「用户 + 场次」累计有效数量，状态为 `reserved` / `ordered` / `refunded` 的记录计入已购

### 3.4 订单与售后

- 订单取消、超时关闭：回补名额并释放限购
- 退款完成：按退款数量回补名额（默认策略，可通过配置关闭）
- 已售罄场次不再补货，除非后台手动调整名额（调整需同步 Redis）

---

## 四、数据模型

### 4.1 迁移文件

遵循现有按域编号的约定，新增：

```text
database/migrations/0003_03_01_000001_create_seckill_tables.php
```

### 4.2 表结构

```php
Schema::create('seckill_activities', static function (Blueprint $table) {
    $table->comment('秒杀活动');
    $table->id();
    $table->tenant();
    $table->string('name', 64)->comment('活动名称');
    $table->string('description')->nullable()->comment('活动说明');
    $table->string('cover')->nullable()->comment('活动封面');
    $table->string('status', 16)
        ->index()
        ->default(SeckillActivityStatus::Draft->value)
        ->comment('活动状态');
    $table->unsignedInteger('sort')->default(0)->comment('排序');
    $table->timestamps();
    $table->softDeletes();

    $table->index(['tenant_id', 'status', 'sort']);
});

Schema::create('seckill_sessions', static function (Blueprint $table) {
    $table->comment('秒杀场次');
    $table->id();
    $table->tenant();
    $table->unsignedBigInteger('activity_id')->index()->comment('活动ID');
    $table->string('name', 64)->comment('场次名称，如 10:00 场');
    $table->unsignedBigInteger('product_id')->index()->comment('商品ID（展示与跳转）');
    $table->unsignedBigInteger('sku_id')->index()->comment('参与秒杀的 SKU');
    $table->decimal('seckill_price', 12)->unsigned()->comment('秒杀价');
    $table->unsignedInteger('stock')->comment('秒杀名额');
    $table->unsignedInteger('sold')->default(0)->comment('已售数量');
    $table->unsignedInteger('limit_per_user')->default(1)->comment('每人限购');
    $table->timestamp('start_at')->index()->comment('开抢时间');
    $table->timestamp('end_at')->index()->comment('结束时间');
    $table->string('status', 16)
        ->index()
        ->default(SeckillSessionStatus::Scheduled->value)
        ->comment('场次状态');
    $table->unsignedInteger('sort')->default(0)->comment('排序');
    $table->timestamps();
    $table->softDeletes();

    $table->index(['tenant_id', 'status', 'start_at']);
    $table->index(['sku_id', 'start_at']);
});

Schema::create('seckill_records', static function (Blueprint $table) {
    $table->comment('秒杀资格与成交记录');
    $table->id();
    $table->tenant();
    $table->unsignedBigInteger('activity_id')->index()->comment('活动ID');
    $table->unsignedBigInteger('session_id')->index()->comment('场次ID');
    $table->user();
    $table->unsignedBigInteger('order_id')->nullable()->index()->comment('订单ID');
    $table->unsignedInteger('qty')->comment('数量');
    $table->decimal('price', 12)->unsigned()->comment('成交单价');
    $table->string('status', 16)
        ->index()
        ->default(SeckillRecordStatus::Reserved->value)
        ->comment('记录状态');
    $table->timestamps();

    $table->unique(['order_id', 'session_id']);
    $table->index(['user_id', 'session_id', 'status']);
});
```

### 4.3 订单侧增量

```php
// orders
$table->string('source', 16)->default('normal')->index()->comment('订单来源：normal/seckill');

// order_items
$table->unsignedBigInteger('seckill_session_id')->nullable()->index()->comment('秒杀场次ID');
```

`order_items.price` 已保存下单快照，秒杀改价不影响历史订单。

### 4.4 枚举

```text
app/Enums/Campaign/SeckillActivityStatus.php   draft / published / closed
app/Enums/Campaign/SeckillSessionStatus.php    scheduled / running / ended / cancelled
app/Enums/Campaign/SeckillRecordStatus.php     reserved / ordered / cancelled / refunded
```

统一实现 `HasLabel` / `HasColor`，中文标签由 `getLabel()` 提供。

### 4.5 模型

```text
app/Models/Campaign/SeckillActivity.php   BelongsToTenant / SoftDeletes / HasEventLog
app/Models/Campaign/SeckillSession.php    BelongsToTenant / SoftDeletes / HasEventLog
app/Models/Campaign/SeckillRecord.php     BelongsToTenant
```

关系：

```php
SeckillActivity::sessions()      // hasMany
SeckillSession::activity()       // belongsTo
SeckillSession::sku()            // belongsTo
SeckillSession::product()        // belongsTo
SeckillSession::records()        // hasMany
SeckillRecord::session()/order() // belongsTo
```

---

## 五、库存与并发

### 5.1 两层库存

| 层 | 存储 | 作用 |
|----|------|------|
| 秒杀名额 | Redis + `seckill_sessions.stock/sold` | 拦截超额流量 |
| 真实库存 | `skus.stock`（条件扣减） | 与普通订单共享，防超卖 |

### 5.2 Redis key

```text
seckill:{tenant_id}:session:{session_id}:stock      剩余名额
seckill:{tenant_id}:session:{session_id}:user:{user_id}  已占用数量
seckill:{tenant_id}:session:{session_id}:state      running / ended
```

TTL 到期时间 = 场次结束时间 + 24 小时。

### 5.3 原子扣减（Lua）

```lua
-- KEYS[1] stock, KEYS[2] user quota
-- ARGV[1] qty, ARGV[2] limit
local stock = tonumber(redis.call('GET', KEYS[1]) or '-1')
if stock < 0 then return -1 end          -- 未预热
if stock < tonumber(ARGV[1]) then return 1 end
local used = tonumber(redis.call('GET', KEYS[2]) or '0')
if used + tonumber(ARGV[1]) > tonumber(ARGV[2]) then return 2 end
redis.call('DECRBY', KEYS[1], ARGV[1])
redis.call('INCRBY', KEYS[2], ARGV[1])
return 0
```

返回码：

| 返回 | 含义 | API 错误 |
|------|------|----------|
| 0 | 成功 | - |
| 1 | 名额不足 | 已售罄 |
| 2 | 超出限购 | 超出限购 |
| -1 | 未预热 | 活动未开始/系统繁忙 |

### 5.4 下单时序

```text
POST 下单
  → 限流（用户 + IP）
  → 幂等校验（request_id）
  → 场次校验（缓存状态 + DB 兜底）
  → Lua 扣名额
  → DB 事务：
      ├─ 条件更新 seckill_sessions: sold += qty（where stock - sold >= qty）
      ├─ OrderService::createOrder（OrderItemDto 传秒杀价）
      ├─ Sku::deductStock（现有条件扣减）
      ├─ 写 order_items.seckill_session_id、orders.source=seckill
      └─ 写 seckill_records(status=ordered)
  → 任一步失败：Lua 回滚名额 + 用户占用，记录 status=cancelled
```

### 5.5 回补

| 触发点 | 位置 | 动作 |
|--------|------|------|
| 取消订单 | `OrderActions\Concerns\ReleasesOrderResources::releaseOrderResources()` | 回补名额、释放限购、记录 `cancelled` |
| 超时关闭 | 同上（订单过期取消走同一动作） | 同上 |
| 退款完成 | `RefundActions\Complete::handle()` | 按退款数量回补、记录 `refunded` |
| 人工调整名额 | 后台操作 | 同步 Redis 并写日志 |

回补必须幂等：以 `seckill_records.status` 为准，重复回调直接跳过。

### 5.6 实现方式

名额只用 Redis 一种实现，不做数据库降级；Redis 不可用时秒杀下单直接返回「系统繁忙」，避免放开限购造成超卖。

`App\Services\Mall\Seckill\RedisSeckillStockCounter`：

| 方法 | 说明 |
|------|------|
| `prepare()` | 场次开始前预热名额与状态 key |
| `reserve()` | Lua 原子扣减名额与用户额度，落 `reserved` 记录，同步 DB `sold` |
| `release()` | 回补名额、置记录终态，同步 DB `sold` |
| `remaining()` | 读取剩余名额；key 缺失时回退数据库值 |

- 名额 key 缺失（未预热）时返回 `NotReady`，不放行
- 测试与生产都走 Redis；测试使用独立 DB（`REDIS_DB=15`）

---

## 六、价格与下单接入

### 6.1 价格解析

现有链路中价格由调用方显式传入 `OrderItemDto`：

- `OrderController::preview()/create()`：`ProductDiscountService::priceFor()`
- `CartController`：结算前先算价

新增 `App\Services\Mall\SeckillPriceResolver`：

```php
public function priceFor(User $user, Sku $sku, ?int $sessionId = null): ?SeckillPrice
```

职责：

1. 校验场次属于当前租户、SKU 匹配、时间窗有效、状态可售
2. 校验限购（读 Redis/DB）
3. 返回秒杀价；不满足时返回 `null`，调用方回退到原价并给出提示

### 6.2 API 请求

秒杀下单只接受：

```json
{
  "session_id": 12,
  "qty": 1,
  "address_id": 3,
  "remark": "",
  "request_id": "uuid"
}
```

**不接收价格**，价格由服务端计算。

### 6.3 改造点

| 文件 | 改动 |
|------|------|
| `OrderController::preview()/create()` | 支持可选 `seckill_session_id`，命中则用秒杀价并跳过会员折扣/优惠券 |
| `CartController` | 秒杀商品不走购物车，接口直接拒绝 |
| `CreateOrder` | 订单写 `source`，明细写 `seckill_session_id`，写 `seckill_records` |
| `ReleasesOrderResources` | 增加秒杀名额回补 |
| `RefundActions\Complete` | 增加按退款数量回补 |

---

## 七、API 设计

统一挂在营销模块前缀下（`routes/apis/campaign.php`）：

| 方法 | 路径 | 说明 | 鉴权 |
|------|------|------|------|
| GET | `/campaign/seckill/activities` | 秒杀活动列表（含进行中/预告场次） | 无 |
| GET | `/campaign/seckill/sessions/{session}` | 场次详情（商品、价格、剩余、限购） | 无 |
| POST | `/campaign/seckill/sessions/{session}/orders` | 秒杀下单 | `auth:sanctum` |
| GET | `/campaign/seckill/sessions/{session}/status` | 轮询剩余名额（可选） | 无 |

响应约定：

- 携带 `server_time`，前端倒计时使用服务端时间
- 场次详情返回 `remaining`（限购剩余与库存剩余）
- 下单成功返回订单号与支付参数，与普通下单一致

错误码：

| 场景 | HTTP | 业务码 |
|------|------|--------|
| 未开始 / 已结束 | 400 | `SECKILL_NOT_STARTED` / `SECKILL_ENDED` |
| 已售罄 | 400 | `SECKILL_SOLD_OUT` |
| 超出限购 | 400 | `SECKILL_LIMIT_EXCEEDED` |
| 重复提交 | 400 | `SECKILL_DUPLICATE` |
| 库存不足 | 400 | `INSUFFICIENT_STOCK` |

---

## 八、Filament 后台

按现有双面板结构（参考 Coupons）：

```text
app/Filament/Backend/Clusters/Campaign/Resources/SeckillActivities/
  Schemas/SeckillActivityForm.php
  Tables/SeckillActivitiesTable.php
  Pages/{List,Create,Edit}SeckillActivities.php
  RelationManagers/SessionsRelationManager.php
  RelationManagers/RecordsRelationManager.php

app/Filament/Tenant/Clusters/Campaign/Resources/SeckillActivities/  （同结构）
```

### 8.1 表单

活动：

- 名称、说明、封面、排序、状态

场次（RelationManager）：

- 名称、商品选择（按租户过滤、仅上架）
- SKU 选择（依赖商品，单规格自动选中）
- 秒杀价、名额、每人限购
- 开始/结束时间

### 8.2 校验

| 规则 | 说明 |
|------|------|
| 秒杀价 < SKU 原价 | 表单校验 + 服务层兜底 |
| 名额 ≤ SKU 当前库存 | 保存时校验 |
| 时间窗不重叠 | 同一 SKU 的 `scheduled/running` 场次不能重叠 |
| 结束时间 > 开始时间 | 表单校验 |
| 活动必须 `published` | 才能被前台读取 |

### 8.3 表格与操作

- 场次列表：状态徽章、已售/名额、剩余、开抢倒计时
- 操作：预热场次（写 Redis，带确认说明）、复制场次、调整名额（同步 Redis）；
  场次的开始/结束时间由时间窗与 `seckill:sync-status` 自动推进，**不提供手动提前开始/结束**，避免绕过规则
- 记录列表：用户、订单号、数量、状态

### 8.4 权限

- 新增 `SeckillActivityPolicy` / `SeckillSessionPolicy`，按 `hasPermission(__CLASS__, __FUNCTION__)` 实现
- 平台端可查看全部租户；租户端仅自己的数据
- 调整名额、手动结束等敏感操作单独授权

---

## 九、定时任务与队列

| 任务 | 频率 | 说明 |
|------|------|------|
| `seckill:sync-status` | 每分钟 | 场次状态切换（scheduled→running→ended）+ 开始前预热 |
| `seckill:reconcile` | 每 5 分钟 | Redis 与 DB `stock - sold` 对账，差异告警 |
| 订单超时取消 | 现有任务 | 触发 `ReleasesOrderResources`，回补名额 |

全部使用 `->onOneServer()`（与现有 `routes/console.php` 一致）。秒杀下单保持同步（用户等待结果），不引入异步建单的复杂度。

---

## 十、监控与风控

### 10.1 指标

- 场次 QPS、下单成功/失败率
- 售罄用时、Redis 扣减失败数
- 名额差异（Redis vs DB）
- 限购拦截次数、重复请求次数

### 10.2 风控

- 限流：复用现有 `api` 限流器，按用户 + IP
- 黑名单：接入 `BlackListService`
- 高频验证码：接入现有 `jason/captcha`
- 幂等：`request_id` 唯一约束 + 缓存
- 审计：场次调整、手动结束写入 `event_logs`

---

## 十一、测试计划

### 11.1 Redis 测试策略

秒杀名额只用 Redis，测试直接连 Redis，不再提供数据库降级实现：

- `phpunit.xml` 设置 `REDIS_DB=15`，与开发数据隔离
- 每个用例 `setUp()` 执行 `flushdb` 清理名额 key
- 用 `RedisSeckillStockCounter::prepare()` 预热后再断言名额、限购与回补
- 未预热场景由 `reserve()` 返回 `NotReady` 覆盖

### 11.2 用例清单

| 类型 | 用例 |
|------|------|
| Unit | 价格解析优先级、Lua 扣减、限购、回补幂等 |
| Feature | 未开始/已结束/售罄/超限/重复请求、下单落库、取消回补、超时回补、退款回补 |
| Concurrency | 并发抢购不超卖（循环 + Redis 计数断言） |
| Filament | 活动/场次 CRUD、时间重叠校验、权限、名额调整 |
| API | 列表、详情、下单、错误码 |

---

## 十二、实施步骤与工期

### Phase 1（MVP，5～8 人天）

1. ✅ 迁移、枚举、模型（1 天）
2. ✅ Redis 计数器（Lua 预扣 / 回补 / 预热）
3. ✅ 预扣、回补、对账命令 + 调度
4. ✅ 价格解析与下单链路接入（1～2 天）
5. ✅ 取消/超时/退款回补（1 天）
6. ✅ Filament 双面板资源（1～2 天）
7. ✅ API（列表/详情/下单）
8. ⏳ 限流 / 幂等 / 验证码

### Phase 2

- 预约提醒、开场排队
- 秒杀数据看板（销量、转化、售罄速度）
- 购物车支持、场次多商品

### Phase 3

- 平台级跨租户大促
- 与拼团/预售共用营销引擎

---

## 十三、风险清单

| # | 风险 | 应对 |
|---|------|------|
| 1 | Redis 与 DB 名额不一致 | 对账任务 + 告警 + 手动修复入口 |
| 2 | 回补重复导致超卖 | 以 `seckill_records.status` 幂等 |
| 3 | 客户端篡改价格 | 只接受 `session_id`，价格服务端计算 |
| 4 | 场次状态延迟 | 下单时实时校验时间窗，不只看状态字段 |
| 5 | 秒杀与普通订单共享 SKU 库存 | 条件扣减 + 发布时校验名额 ≤ 库存 |
| 6 | 租户串数据 | API、缓存 key、Filament 全部带 `tenant_id` |
| 7 | 退款回补策略分歧 | 配置项 `seckill.restore_on_refund` 控制 |
| 8 | 时钟偏差 | 以服务端时间为准，接口返回 `server_time` |

---

## 十四、验收标准

- [ ] 后台可创建活动与多场次，场次绑定 SKU、价格、名额、限购
- [ ] 同一 SKU 时间重叠被拒绝
- [ ] 前台可见进行中与预告场次，倒计时使用服务端时间
- [ ] 未开始/已结束/售罄/超限购/重复请求均返回明确错误
- [ ] 并发下单不超名额、不超 SKU 库存
- [ ] 取消、超时、退款按规则回补且幂等
- [ ] 平台端与租户端数据隔离
- [ ] 对账任务可发现并告警名额差异
- [ ] 相关单元/功能测试通过

---

## 十五、实现进度（2026-11-06）

### 15.1 已完成

| 模块 | 状态 | 位置 |
|------|------|------|
| 迁移（3 张表 + `orders.source` + `order_items.seckill_session_id`） | ✅ | `database/migrations/0003_03_01_000001_create_seckill_tables.php` |
| 枚举（活动 / 场次 / 记录 / 预扣失败） | ✅ | `app/Enums/Campaign/Seckill*` |
| 模型与关系 | ✅ | `app/Models/Campaign/Seckill*` |
| Redis 计数器（Lua 预扣 / 回补 / 预热 / 剩余） | ✅ | `app/Services/Mall/Seckill/RedisSeckillStockCounter.php` |
| 秒杀价解析 | ✅ | `app/Services/Mall/Seckill/SeckillPriceResolver.php` |
| 秒杀下单（预扣 → 建单 → 绑定记录 → 失败回补） | ✅ | `app/Services/Mall/Seckill/SeckillOrderService.php` |
| 订单集成（`source` / `seckill_session_id`） | ✅ | `OrderService::createOrder()` / `OrderActions\CreateOrder` |
| 取消 / 超时回补 | ✅ | `OrderActions\Concerns\ReleasesOrderResources` |
| 退款回补（全单退款） | ✅ | `RefundActions\Complete` + `SeckillReservationService::releaseForRefund()` |
| 场次状态同步与预热命令 | ✅ | `app/Console/Commands/SeckillSyncStatus.php`（每分钟） |
| 名额对账命令 | ✅ | `app/Console/Commands/SeckillReconcile.php`（每 5 分钟） |
| 核心链路测试（6 用例） | ✅ | `tests/Feature/Mall/SeckillOrderTest.php` |
| 测试 Redis 隔离 | ✅ | `phpunit.xml` 设置 `REDIS_DB=15`，用例 `flushdb` |
| Filament 双面板资源（活动 + 场次） | ✅ | `app/Filament/{Backend,Tenant}/Clusters/Campaign/Resources/Seckill*` |
| 表单 / 表格双面板共用 | ✅ | `app/Filament/{Forms,Tables}/Campaign` |
| 权限策略 | ✅ | `app/Policies/Campaign/Seckill*Policy.php` |
| 表单/保存校验（价格、名额、时间重叠） | ✅ | `SeckillSession::booted()` |
| 后台操作（预热） | ✅ | `SeckillSessionPreheatAction`（带确认弹窗） |
| 前台 API（列表 / 详情 / 下单） | ✅ | `SeckillController` + `Http/Resources/Campaign/Seckill*Resource` |
| API 测试（4 用例） | ✅ | `tests/Feature/Campaign/SeckillApiTest.php` |
| 后台页面测试（2 用例） | ✅ | `tests/Feature/Campaign/SeckillResourceTest.php` |
| 活动详情查看场次 | ✅ | `Infolists/Campaign/SeckillActivityInfolist`（场次数量 + 剩余名额图表）+ `RelationManagers/Campaign/SeckillSessionsRelationManager` |
| 场次操作按钮 | ✅ | `app/Filament/Actions/Campaign/SeckillSessionPreheatAction` |
| 测试数据命令 | ✅ | `app/Console/Commands/Seeders/SeckillSeeder.php`（`seed:seckill`） |

已实现口径说明：

- 名额只走 Redis（Lua），Redis 不可用时返回「系统繁忙」，不做数据库降级
- 下单成功后 `orders.source=seckill`，明细写入 `seckill_session_id`，价格使用服务端秒杀价
- 取消/超时按 `seckill_records.status` 幂等回补；退款目前仅在**全单退款完成**时回补
- 后台活动与场次拆成两个 Resource，表单/表格放在 `App\Filament\Shared\Campaign` 供两端共用
- 平台端创建时需要选择租户；租户端自动归属当前租户

### 15.2 测试数据

已为租户 1 生成 1 个活动 + 3 个场次（1 个进行中、2 个待开始）。命令会同时预热 Redis。

```bash
# 交互式：选择租户与场次数量
php artisan seed:seckill

# 非交互
php artisan seed:seckill --tenant=1 --sessions=3
```

场次取自该租户有库存的上架 SKU，秒杀价按原价 7 折生成，名额默认 `min(50, SKU 库存)`。

### 15.3 待完成

| 模块 | 状态 | 说明 |
|------|------|------|
| 限流 / 幂等 / 验证码 | ⏳ | 用户 + IP 限流、`request_id` 幂等 |
| 后台运营操作 | ⏳ | 复制场次、调整名额（同步 Redis） |
| 部分退款回补 | ⏳ | 当前仅全单退款回补 |
| 并发压测 | ⏳ | 真实 Redis 下的抢购并发用例 |
