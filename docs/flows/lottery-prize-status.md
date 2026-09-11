# 抽奖奖品状态流转图

> 抽奖奖品记录状态（`App\Enums\Campaign\LotteryPrizeStatus`），模型 `App\Models\Campaign\LotteryPrizeRecord`。该枚举**未实现 `HasStateMachine`**，流转由 `App\Services\Campaign\LotteryService` 维护。一条中奖记录 = 一次抽奖命中的非「谢谢参与」奖品；记录里冗余保存 `type`（奖品类型）与 `prize_detail`（奖品配置快照），兑付动作按 `type` 分流。奖品类型见 `LotteryPrizeType`：余额 / 积分 / 优惠券 / 红包 / 实物 / 谢谢参与。

## 一、状态说明

| 状态 | 枚举 | 值 | 颜色 | 说明 |
|------|------|-----|------|------|
| 待兑奖 | Pending | `pending` | warning | 中奖后写入，等待兑付（**当前唯一可达状态以外的兑付入口有限，见第六节**） |
| 已兑奖 | Fulfilled | `fulfilled` | success | 优惠券已发放 / 实物已线下兑付 |
| 已过期 | Expired | `expired` | gray | 兑奖超时（**未实现**） |
| 已取消 | Cancelled | `cancelled` | danger | 取消奖品并回增库存 |

---

## 二、状态流转图

```mermaid
stateDiagram-v2
    [*] --> Pending : draw() 抽奖命中非「谢谢参与」奖品<br/>（同事务原子扣减奖品库存）

    Pending --> Fulfilled : fulfillPrize() 兑奖<br/>（优惠券：发券后置已兑付 / 实物：线下兑付）
    Pending --> Cancelled : cancelPrize() 取消奖品<br/>（回增 prize.remaining_quantity）

    state "已过期 Expired（未实现）" as Expired
    Pending -.-> Expired : 无过期任务

    Fulfilled --> [*]
    Cancelled --> [*]

    classDef pending fill:#f59e0b,stroke:#d97706,color:white
    classDef fulfilled fill:#10b981,stroke:#059669,color:white
    classDef cancelled fill:#ef4444,stroke:#dc2626,color:white
    classDef missing fill:#e5e7eb,stroke:#9ca3af,color:#374151

    class Pending pending
    class Fulfilled fulfilled
    class Cancelled cancelled
    class Expired missing
```

**状态流转：**

- 抽奖命中非 `None` 奖品时创建记录并置 `Pending`（`LotteryService` 抽奖事务内，命中 `None` 记 `LotteryDraw` 但不建奖品记录）
- `Pending` → `Fulfilled`：按类型分流
  - **优惠券**：事务内调用 `CouponService::sendToUser()` 发放券实例，成功后再置 `Fulfilled`；券停用 / 达发放上限等失败会抛异常并**保持 `Pending`**，留待人工换券或取消
  - **实物**：直接置 `Fulfilled`，写 `fulfillment_note` 与 `fulfilled_at`
- `Pending` → `Cancelled`：写取消原因到 `fulfillment_note`，并在奖品 `total_quantity > 0` 时回增 `remaining_quantity`
- 两个动作都要求精确处于 `Pending`，否则抛「该奖品不可兑奖 / 不可取消」

---

## 三、状态流转规则

| 当前状态 | 可流转到 | 触发 | 校验位置 |
|----------|----------|------|----------|
| - | Pending | 抽奖命中奖品 | `app/Services/Campaign/LotteryService.php:103` |
| Pending | Fulfilled | 实物兑奖 | 同上（:248-269） |
| Pending | Fulfilled | 优惠券兑付（发券成功） | 同上（:284-316） |
| Pending | Cancelled | 取消奖品 | 同上（:328-344） |
| Pending | Fulfilled / Cancelled | 非 Pending 调用 | 抛「该奖品不可兑奖 / 不可取消」（:261、:286、:330） |
| Expired | -（无入口） | 自动过期 | 未实现 |

**按奖品类型的兑付出口：**

| 奖品类型 | 是否可兑付 | 说明 |
|----------|------------|------|
| Coupon 优惠券 | ✅ | `fulfillCouponPrize()` 发券 |
| Physical 实物 | ✅ | `fulfillPrize()` 人工标记兑付 |
| Balance 余额 / Points 积分 / Redpack 红包 | ❌ | `fulfillPrize()` 对非 Coupon / Physical 抛「仅实物奖品需要兑奖」，**这三类奖品没有任何兑付路径**，记录会永久停在 `Pending` |

---

## 四、操作对应状态流转

来源：`App\Services\Campaign\LotteryService`、`app/Filament/{Backend,Tenant}/Clusters/Campaign/Resources/Lotteries/RelationManagers/PrizeRecordsRelationManager.php`。

| 操作 | 方法 | 入口 | 前置状态 | 后置状态 |
|------|------|------|----------|----------|
| 抽奖 | `draw()` | 用户端 `POST /lotteries/{lottery}/draw`（`routes/apis/campaign.php:63`） | 活动进行中、有可用次数 | 命中即建 Pending 记录 |
| 查询我的奖品 | - | `GET /lotteries/{lottery}/prizes` | 任意 | 不变 |
| 兑奖（批量） | `fulfillPrize()` | 中奖记录「批量兑奖」BulkAction（后台 / 租户两侧，跳过不可兑奖的记录） | Pending | Fulfilled |
| 取消奖品 | `cancelPrize()` | **无界面入口**（只能代码调用） | Pending | Cancelled |

**管理入口：** 中奖记录表支持按状态与奖品类型筛选，展示兑奖备注与兑奖时间；`LotteryStatsWidget` 以 `Pending` 统计待兑奖数量。

---

## 五、副作用

| 时点 | 副作用 | 位置 |
|------|--------|------|
| 抽奖命中 | 原子扣减奖品库存（`decrementQuantity()`），创建 `LotteryDraw` + `LotteryPrizeRecord` | `LotteryService::draw()`（:82-105、:198） |
| 兑付优惠券奖品 | 事务内 `CouponService::sendToUser()` 发放券实例（用户侧得到一张可用的券） | `fulfillCouponPrize()`（:308-316） |
| 兑奖（实物） | 写 `fulfillment_note` / `fulfilled_at`，线下履约 | `fulfillPrize()`（:265-269） |
| 取消奖品 | 写 `fulfillment_note`（取消原因）+ 回增 `lottery_prizes.remaining_quantity` | `cancelPrize()`（:334-343） |

**无资金变动**（余额 / 积分类奖品本应产生账户变动，但兑付路径缺失，见第六节）。

**并发与幂等：** 兑奖与取消都以「状态必须为 Pending」为守卫，但校验在事务外，无行锁；重复点击批量兑奖时可能重复发券（券侧有发放上限与锁保护，见 [优惠券状态流转图](coupon-status.md)）。

---

## 六、实现缺口

| 缺口 | 影响 | 相关位置 |
|------|------|----------|
| 余额 / 积分 / 红包奖品无兑付路径 | 这三类奖品记录永久停留在 `Pending`，用户在余额 / 积分账户上收不到奖励 | `LotteryService::fulfillPrize()`（:257） |
| `Expired` 无写入方 | 兑奖无有效期概念，也没有过期任务 | 无定时任务 |
| 取消无界面入口 | `cancelPrize()` 只能代码调用，运营无法在后台取消问题奖品 | 中奖记录表无取消动作 |
| 兑奖无幂等保护 | 状态校验在事务外且无行锁，并发批量兑奖可能重复发券 | `fulfillPrize()`（:261） |
| 兑奖失败不可见 | 优惠券发放失败抛异常后保持 Pending，无告警 / 待处理列表 | - |

---

## 七、终态说明

| 状态 | 说明 | 是否可删除 |
|------|------|------------|
| Fulfilled | 已兑奖，终态 | 否（关联 `LotteryDraw`） |
| Cancelled | 已取消并回增库存，终态 | 否 |
| Expired | 枚举已定义，未实现 | - |
