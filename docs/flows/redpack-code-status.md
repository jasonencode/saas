# 红包码状态流转图

> 红包码状态（`App\Enums\Campaign\RedpackCodeStatus`），模型 `App\Models\Campaign\RedpackCode`（码本身即唯一凭证，`code` 字段唯一）。该枚举**未实现 `HasStateMachine`**，流转由 `App\Services\Campaign\RedpackService::claim()` 与 `App\Jobs\Campaign\SendRedpackJob` 维护。资金发放走微信支付「商家转账」接口，因此状态里同时包含业务领取态（`Claimed`）与通道发放态（`Sending` / `Sent` / `Failed`）。

## 一、状态说明

| 状态 | 枚举 | 值 | 颜色 | 说明 |
|------|------|-----|------|------|
| 待领取 | Active | `active` | primary | 批量生成后的初始状态，可被领取 |
| 已领取 | Claimed | `claimed` | success | 用户已领取，等待异步发放 |
| 发放中 | Sending | `sending` | info | Job 已开始调用微信商家转账 |
| 已发放 | Sent | `sent` | success | 微信侧发放成功，写 `bill_no` |
| 发放失败 | Failed | `failed` | danger | 通道异常，Job 抛出后由队列重试 |
| 禁用 | Disabled | `disabled` | warning | **仅后台手工编辑可得**，无业务写入方 |

---

## 二、状态流转图

```mermaid
stateDiagram-v2
    [*] --> Active : createCodesBulk() 批量生成<br/>（CreateCodeBulkAction，固定 / 随机金额）

    Active --> Claimed : claim() 用户领取<br/>（校验活动有效 + 码可领取，写领取人/IP/时间）
    Claimed --> Sending : Job 开始发放<br/>（SendRedpackJob 写 bill_no）
    Sending --> Sent : 微信商家转账成功
    Sending --> Failed : 通道异常（抛出，Job 重试 3 次）
    Failed --> Sending : 重试重新发放

    state "禁用 Disabled（仅后台手工编辑）" as Disabled
    Active -.-> Disabled : Filament 编辑表单手工改
    Claimed -.-> Disabled : Filament 编辑表单手工改

    Sent --> [*]
    Disabled --> [*]

    classDef active fill:#3b82f6,stroke:#2563eb,color:white
    classDef claimed fill:#10b981,stroke:#059669,color:white
    classDef sending fill:#0ea5e9,stroke:#0284c7,color:white
    classDef sent fill:#059669,stroke:#047857,color:white
    classDef failed fill:#ef4444,stroke:#dc2626,color:white
    classDef manual fill:#f59e0b,stroke:#d97706,color:white

    class Active active
    class Claimed claimed
    class Sending sending
    class Sent sent
    class Failed failed
    class Disabled manual
```

**状态流转：**

- 生成 → `Active`
- `Active` → `Claimed`：领取时校验「活动 `isActive()`」与「码 `isClaimable()`（即 `status === Active`）」，写 `user_id` / `claimed_at` / `claimed_ip`，随后派发 `SendRedpackJob`
- `Claimed` → `Sending` → `Sent` / `Failed`：异步发放链路
- `Failed` → `Sending`：队列重试（`public int $tries = 3`、`timeout = 60`），重试会重新走一次 `Sending → Sent / Failed`
- `Disabled`：**没有业务代码写入**，只能通过后台红包码列表的「编辑」表单手工选状态（编辑表单的 `status` 下拉包含全部 6 个状态）

---

## 三、状态流转规则

| 当前状态 | 可流转到 | 触发 | 校验位置 |
|----------|----------|------|----------|
| - | Active | `createCodesBulk()` | `app/Services/Campaign/RedpackService.php:55` |
| Active | Claimed | `claim()` | 同上（:106，校验 `isActive()` 与 `isClaimable()`） |
| 非 Active | -（拒绝领取） | `claim()` | 抛「红包码无效或已被领取」（:114） |
| Claimed | Sending | `SendRedpackJob::handle()` | `app/Jobs/Campaign/SendRedpackJob.php:58` |
| Sending | Sent | 转账成功 | 同上（:78） |
| Sending | Failed | 转账异常（抛出以便重试） | 同上（:80） |
| Failed | Sending | 队列重试 | 同上（`:tries = 3`） |
| 任意 | Disabled | 后台手工编辑 | `CodesRelationManager::form()`（`status` 下拉） |

**发放前的前置检查（不满足则 `return`，状态停留 `Claimed`）：**

| 检查 | 条件 | 位置 |
|------|------|------|
| 活动仍有效 | `!$redpack->isActive()` | `SendRedpackJob.php:37` |
| 领取用户存在 | `!$user` | 同上（:42） |
| 有微信 openid | 用户微信绑定为空 | 同上（:47） |
| 有可用商户配置 | 租户下无启用的 `WechatPayment` | 同上（:52） |

---

## 四、操作对应状态流转

来源：`App\Services\Campaign\RedpackService`、`App\Jobs\Campaign\SendRedpackJob`、`app/Filament/Actions/Campaign/CreateCodeBulkAction.php`。

| 操作 | 方法 | 入口 | 前置状态 | 后置状态 |
|------|------|------|----------|----------|
| 批量生成 | `createCodesBulk()` | 红包活动 → 红包码「批量创建」（租户 / 后台） | - | Active |
| 领取 | `claim()` | 用户端 `POST /redpacks/{code}/claim`（`routes/apis/campaign.php:51`） | Active（且活动有效） | Claimed |
| 异步发放 | `SendRedpackJob::handle()` | 领取后自动入队 | Claimed（且四项前置检查通过） | Sent / Failed |
| 手工改状态 | Filament `EditAction` | 红包码列表「编辑」 | 任意 | 任意（含 Disabled） |
| 导出 | `exportCodesToZip()` | 活动导出（CSV + ZIP） | 任意 | 不变 |

---

## 五、副作用

| 时点 | 副作用 | 位置 |
|------|--------|------|
| 批量生成 | 逐条 `codes()->create()`，码用 `Str::random()` 生成并以「数据库已存在」判重（**非原子**，靠 `code` 唯一约束兜底）；随机模式金额取 `[min, max]` 且下限 0.3 元 | `RedpackService::createCodesBulk()`（:35-60） |
| 领取 | 写领取人 / IP / 时间；派发 `SendRedpackJob` | `RedpackService::claim()`（:118-125） |
| 发放成功 | 行内写入 `bill_no`（`RP` + 时间戳 + 随机串），调用 `WechatPaymentService::sendRedpack()`（微信商家转账） | `SendRedpackJob::handle()`（:56-78） |
| 发放失败 | 置 `Failed` 后**重新抛出异常**，交给队列重试与失败队列 | 同上（:79-83） |
| 统计 | 活动已领取数量按 `Claimed` 统计（`Redpack::claimedCodes()`） | `app/Models/Campaign/Redpack.php:42` |

**并发与幂等：**

- 领取的「可领取」判断与状态更新之间**没有锁 / 条件更新**，同一码被并发领取时可能都通过 `isClaimable()` 检查，后写覆盖前写（`code` 唯一约束不覆盖此场景）
- 发放重试是「重新置 `Sending` 再调用通道」，若首次已实际到账但回调判定失败，存在重复发放风险，`bill_no` 每次重试都会重新生成

---

## 六、实现缺口

| 缺口 | 影响 | 相关位置 |
|------|------|----------|
| 领取无原子保护 | 并发领取可能重复占用同一个码 | `RedpackService::claim()`（:114-123） |
| 前置检查静默返回 | 无 openid / 无商户配置时 Job 直接 `return`，码永久停在 `Claimed`，无告警也无重发入口 | `SendRedpackJob::handle()`（:35-54） |
| `Disabled` 无业务写入方 | 只能手工编辑，无「停用码」的业务动作 | 编辑表单（`CodesRelationManager::form()`） |
| 编辑表单可任意改状态 | 后台可把 `Sent` 改回 `Active` 造成重复发放 | 同上 |
| 无重发入口 | 发放失败只能靠队列重试耗尽，之后无界面重试 | 红包码列表无重发动作 |
| 生成判重非原子 | 码生成靠「查库 + 唯一约束兜底」，大并发下会撞唯一键报错 | `RedpackService::generateCode()`（:70） |

---

## 七、终态说明

| 状态 | 说明 | 是否可删除 |
|------|------|------------|
| Sent | 已发放，资金已通过微信转出 | 否（有资金流水） |
| Disabled | 已禁用，不可再领取 | 是（列表提供删除动作） |
| Failed | 可重试，非终态 | 是 |
