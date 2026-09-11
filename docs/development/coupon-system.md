# 优惠券系统开发方案

> 优惠券系统现状盘点与核销链路开发方案。现有实现覆盖「定义 → 发放 → 领取 → 查询」，**下单使用（核销）链路完全缺失**；本方案补全「结算 → 核销 → 金额扣减 → 退款分摊 → 返还」闭环，并处理并发安全与抽奖券奖品闭环。

## 一、现状盘点

### 1.1 数据模型

迁移文件 `database/migrations/0003_03_00_000001_create_coupons_table.php`，四张表：

| 表 | 用途 | 关键字段 | 说明 |
|----|------|----------|------|
| `coupons` | 优惠券定义 | name / code(unique) / type / value / min_amount / max_discount / usage_limit / usage_limit_per_user / expired_type / days / start_at / end_at / status | 租户维度（`tenant()`），软删除 |
| `coupon_user` | 用户持券记录 | user_id / coupon_id / expired_at / is_used / used_at | **一行 = 一张券**（`sendToUser` 按 qty 循环插入），自增主键，无 (coupon_id, user_id) 唯一约束 |
| `coupon_product` | 适用商品范围 | coupon_id + product_id 复合主键 | **仅数据关联，无任何使用校验逻辑** |
| `coupon_order` | 订单用券记录 | order_id + coupon_id 复合主键 / coupon_user_id / discount_amount | **无业务写入**（仅统计与测试清理引用） |

类型与有效期枚举：

| 枚举 | 取值 | 说明 |
|------|------|------|
| `CouponType` | `fixed`（固定金额）/ `percent`（百分比） | percent 受 `max_discount` 封顶 |
| `ExpiredType` | `receive`（领取后 N 天）/ `fixed`（固定起止） | receive 时 `coupon_user.expired_at` 领取时落库 |

### 1.2 领域校验（`Coupon` 模型）

| 方法 | 逻辑 | 现状 |
|------|------|------|
| `isValid()` | status 启用 + start_at/end_at 有效期内 | 被 API 与服务使用 |
| `canBeUsed()` | isValid + 总发放量未超 `usage_limit` | 被 API 使用 |
| `canUserUse()` | canBeUsed + 每人限领校验 | **死代码，无调用方** |

### 1.3 已实现能力

| 层 | 位置 | 内容 |
|----|------|------|
| 服务 | `CouponService` | `calculateDiscount()`（折扣计算：有效期/可用性/min_amount 校验 + fixed/percent 分支）、`sendToUser()`（发放：总限/限领精确校验 + 过期时间计算 + 事务批量插入） |
| API | `routes/apis/campaign.php` | 5 个接口：可领券列表、详情、领取（claim）、我的券、数量统计 |
| Filament | Tenant / Backend 双面板 | Coupon 资源（表单/表格/详情）+ 三个 RelationManager（用户、订单、商品）+ 统计 Widget |
| 测试 | `tests/Feature/Campaign/CouponServiceTest.php` | 17 个用例：折扣计算 9 个 + 发放限制 8 个 |
| 交叉引用 | `OrderController`（我的可用券数）、`ProductScopes`（discount 筛选）、抽奖奖品（`LotteryPrizeType::Coupon`） | |

### 1.4 金额口径现状

```
订单实付 total_amount = amount（商品总额） + freight（运费）     // Order::getTotalAmount()
退款金额 = Σ(order_items.price × qty) + 运费                  // RefundService::calculateRefundAmount()
开票金额 = Σ(关联订单 total_amount)                           // InvoiceService
```

**无优惠券抵扣字段**，支付（`ShouldPayment`）、结算（`ShouldSettlement`）、退款、发票均基于此口径。

---

## 二、缺口与缺陷

| # | 问题 | 级别 | 说明 |
|---|------|------|------|
| 1 | **下单核销链路缺失** | 🔴 | `OrderService::createOrder/createOrders` 不接收券参数；`coupon_order` 无写入；`coupon_user.is_used` 无核销更新；结算预览不支持券。用户领了券**无处可用** |
| 2 | **订单金额无抵扣字段** | 🔴 | orders 表无 `coupon_discount`，`total_amount` 口径不含券，支付金额无法扣减 |
| 3 | **适用商品范围无校验** | 🔴 | `coupon_product` 关联可在后台维护，但 `calculateDiscount()` 不校验商品范围，设置了「仅限指定商品」的券实际全场可用 |
| 4 | **退款/取消不返还券** | 🔴 | 订单取消、退款完成均不释放已用券（即使核销链路补上，也需返还逻辑） |
| 5 | **退款金额无实付上限** | 🔴 | `calculateRefundAmount()` 按订单项单价计算、与实付无关——引入券抵扣后，**全额退款会退超过实付金额**（资损）。必须按券抵扣分摊 |
| 6 | **发放并发超发** | 🟡 | `sendToUser()` 为 check-then-act：先 count 再插入，无锁；`coupon_user` 无唯一约束兜底，高并发下可突破总限/限领 |
| 7 | **抽奖券奖品不闭环** | 🟡 | `LotteryService::draw()` 抽中 Coupon 奖品只创建 `PrizeRecord(Pending)`；`fulfillPrize()` 仅支持 Physical，优惠券奖品永远停在 Pending，不会发到 `coupon_user` |
| 8 | **死代码** | 🟡 | `Coupon::canUserUse()`、`ValidCouponRule`（校验 coupon_id 但无调用方，且语义与核销需求不符） |
| 9 | **float 计算** | 🟡 | `calculateDiscount()` 用 float 乘法，与项目 bcmath 口径不一致（订单金额链路全为 bcmath + decimal:2） |
| 10 | **code 字段无业务用途** | 🟡 | unique 约束 + 表单必填，但无兑换码场景（红包用 code 领取，券用 id）。**已确认移除**，见 3.9 |

---

## 三、开发方案：核销链路

### 3.1 数据模型调整

**orders 表新增抵扣字段**（按项目惯例直接修改源迁移文件 `0003_02_00_000001_create_orders_table.php`）：

```php
$table->decimal('coupon_discount', 12)
    ->unsigned()
    ->default(0)
    ->comment('优惠券抵扣金额');
```

**金额口径调整为：**

```
total_amount = amount + freight - coupon_discount
```

`Order::getTotalAmount()` 改为 `bcadd(bcsub($this->amount, $this->coupon_discount, 2), $this->freight, 2)`；`coupon_discount` 默认 0。开票金额（`InvoiceService` 按 `total_amount` 累加）与结算自动跟随实付口径，无需改动。

> 项目迁移为改源文件 + 全量重建模式，**不考虑存量数据兼容**。

> `coupon_order` 表保持现状（已确认：**一单一券**，复合主键 `order_id + coupon_id` 即此语义；`coupon_user_id` 已有字段可反查券实例）。核销幂等由服务层乐观更新保证（见 3.5）。

### 3.2 核销参数与跨店拆单

- **传参用 `coupon_user_id`**（用户持有的券实例 ID），而非 coupon_id——核销必须落到具体券实例（`is_used`），且限领/过期均以实例为准
- **跨店拆单规则**：购物车为跨店购物车，`createOrders()` 按租户分组拆单（`OrderItemDto::$tenantId`）；优惠券是租户维度，**一张券只能抵扣其所属租户的那笔子订单**
- `createOrders()` 增加 `?int $couponUserId` 参数：定位券实例所属租户，仅透传给该租户的子订单；券租户不在本次拆单范围内或与商品租户不符 → 拒绝整单（提示「优惠券不适用于所选商品」）

### 3.3 CouponService 改造

#### 3.3.1 折扣计算（bcmath 化 + 商品范围）

```php
/**
 * 计算折扣金额（bcmath，保留 2 位）
 *
 * @param  Coupon  $coupon  优惠券
 * @param  string  $baseAmount  抵扣基数（适用商品小计）
 *
 * @throws InvalidArgumentException 券失效、不可用或金额不满足条件
 *
 * @return string 折扣金额
 */
public function calculateDiscount(Coupon $coupon, string $baseAmount): string
{
    if (!$coupon->isValid()) {
        throw new InvalidArgumentException('优惠券已失效');
    }

    if (!$coupon->canBeUsed()) {
        throw new InvalidArgumentException('优惠券不可用');
    }

    if ($coupon->min_amount && bccomp($baseAmount, (string) $coupon->min_amount, 2) === -1) {
        throw new InvalidArgumentException(
            sprintf('订单金额未满足使用条件，最低需要 ￥%s', number_format($coupon->min_amount, 2))
        );
    }

    return match ($coupon->type) {
        // 固定金额：抵扣不超过基数（实付不为负）
        CouponType::Fixed => bccomp((string) $coupon->value, $baseAmount, 2) === 1
            ? $baseAmount
            : (string) $coupon->value,
        // 百分比：基数 × value ÷ 100，受 max_discount 封顶
        CouponType::Percent => $this->capPercentDiscount($baseAmount, $coupon),
    };
}

/**
 * 计算券对一组订单项的抵扣基数
 *
 * coupon_product 为空 → 全场适用（该租户），基数 = 订单项总额；
 * 非空 → 基数 = 订单项中命中适用商品的小计；未命中任何商品返回 null（券不可用）。
 *
 * @param  Coupon  $coupon  优惠券
 * @param  Collection<OrderItemDto>  $items  订单项（同租户）
 *
 * @return string|null 抵扣基数，券不适用时 null
 */
public function baseAmountFor(Coupon $coupon, Collection $items): ?string
```

`baseAmountFor` 实现要点：`$coupon->products()->pluck('products.id')` 一次查询取适用商品 ID 集合（空集合 = 全场），仅对 `orderable instanceof Sku` 的订单项按商品命中过滤累加小计。

#### 3.3.2 预览与核销（单一计算路径）

预览与下单**必须复用同一条计算路径**，保证金额一致：

```php
/**
 * 预估券对一组订单项的抵扣金额（预览用，不落库）
 *
 * @throws InvalidArgumentException 券不可用（校验见 applyToOrder）
 *
 * @return string{base_amount: string, discount: string} 基数与抵扣金额
 */
public function previewDiscount(CouponUser $couponUser, User $user, Collection $items): array
```

> **最低实付规则（已确认）**：抵扣后订单实付不足 0.01 时，clamp 抵扣金额使实付 = 0.01，**必须走支付流程**。clamp 在 `previewDiscount` / `applyToOrder` 共用的内部计算方法中执行（两者同路径，金额必然一致）：
>
> ```php
> // 券不作用于运费，clamp 商品实付最低 0.01：coupon_discount ≤ amount - 0.01
> // $orderAmount = 全部订单项小计（含不适用商品，区别于 base_amount）
> $discount = min($discount, bcsub($orderAmount, '0.01', 2));
> ```
>
> 与 3.3.1 的两层关系：`calculateDiscount` 内层保证「抵扣 ≤ 抵扣基数」（fixed 分支），外层 clamp 保证「订单商品实付 ≥ 0.01」——限定商品券的基数小于订单总额时，两层约束独立生效。

```php
/**
 * 核销券到订单（在 OrderService::createOrder 事务内调用）
 *
 * 校验（按序）：
 *  1. 券实例归属当前用户（coupon_user.user_id）
 *  2. 券未使用（is_used = false）
 *  3. 券实例未过期（expired_at 为空或晚于当前）
 *  4. 券定义 isValid()
 *  5. 券租户 = 订单租户
 *  6. baseAmountFor 非空（适用商品范围）
 *  7. 内部计算抵扣金额（含 min_amount 校验与 0.01 clamp，与 previewDiscount 同路径）
 *
 * 写入：
 *  - coupon_user：乐观核销（见 3.5），affected=0 → 抛「优惠券已被使用」回滚
 *  - orders.coupon_discount = 抵扣金额
 *  - coupon_order：(order_id, coupon_id, coupon_user_id, discount_amount)
 *
 * @return string 实际抵扣金额
 */
public function applyToOrder(CouponUser $couponUser, Order $order, Collection $items): string
```

#### 3.3.3 释放

```php
/**
 * 释放订单占用的券（未支付取消 / 全额退款完成时调用）
 *
 * 幂等：无 coupon_order 记录则 no-op。
 * 写入：删除 coupon_order、orders.coupon_discount 归零、
 *       coupon_user.is_used=false / used_at=null
 *
 * 注意：返还时券实例若已过期，仍恢复 is_used=false（过期态由 expired_at 表达，
 * 用户侧自然显示为过期券，不重复占用发放额度）。
 */
public function releaseFromOrder(Order $order): void
```

> 核销/释放不引入新事件：均发生在订单事务内，通过 `OrderService::log()` 的 context 记录 `coupon_user_id / coupon_discount`，与现有订单日志模式一致。

#### 3.3.4 发放加锁

```php
public function sendToUser(Coupon $coupon, User $user, int $qty = 1): void
{
    $lock = Cache::lock("coupon_send_{$coupon->getKey()}", 10);

    $lock->block(5, function () use ($coupon, $user, $qty) {
        // 现有校验与插入逻辑不变（锁内 count 校验 → 插入）
    });
}
```

复用项目已有 `Cache::lock` 模式（`CartController::createFromCart`），锁粒度为券维度，互斥同一券的并发发放。

#### 3.3.5 过期券定时清理（已确认：定时任务物理处理）

新增 Artisan 命令，参照 `IdentityExpireCommand` 模式：

```php
#[Signature('app:campaign:coupon-expire')]
#[Description('自动清理用户已过期的优惠券')]
class CouponExpireCommand extends Command
{
    public function handle(): int
    {
        // chunkById 批量删除：is_used = false 且 expired_at <= now() 的券实例
    }
}
```

调度注册于 `routes/console.php`（与现有任务一致）：

```php
// 优惠券过期自动清理
Schedule::command('app:campaign:coupon-expire')
    ->daily()
    ->onOneServer();
```

**清理范围与口径影响：**

| 项 | 说明 |
|----|------|
| 清理对象 | 仅「未使用且已过期」的 `coupon_user` 记录（物理删除） |
| 保留对象 | `is_used = true` 的已核销记录（`coupon_order.coupon_user_id` 依赖，核销历史不丢） |
| 发放量额度 | `usage_limit` / `usage_limit_per_user` 统计基于 `coupon_user` 计数——过期券清理后额度释放，可重新领取（口径变为「当前有效持有」，与身份清理行为一致） |
| 用户侧展示 | 「我的券」过期券在清理后不再返回；stats 的 expired 计数仅覆盖最近一个清理周期（≤ 24h 窗口） |
| 查询兼容 | 各查询处的 `expired_at` 过滤条件保留（覆盖清理周期内的边界窗口），不因清理移除 |

### 3.4 与身份折扣的叠加规则

延续 [identity-discount.md](identity-discount.md) 已确认的规则，**券的抵扣基数按折后单价计算**，且**抵扣不作用于运费**：

```
SKU 单价（含身份折扣）→ 订单项小计 → 适用商品小计 = 券抵扣基数
min_amount 判断、fixed/percent 计算均基于该基数
运费不参与抵扣基数，也不被折扣
```

### 3.5 并发安全与幂等

| 场景 | 方案 |
|------|------|
| **核销防重复使用** | 乐观更新：`CouponUser::whereKey($id)->where('is_used', false)->update(['is_used' => true, 'used_at' => now()])`，affected=0 视为已被使用，事务回滚 |
| **发放防超发** | `sendToUser()` 包裹 `Cache::lock("coupon_send_{coupon_id}", 10)`（见 3.3.4） |
| **下单防重复提交** | 复用现有 `mall_order_{user}` 锁，无需新增 |
| **释放幂等** | `releaseFromOrder()` 以 coupon_order 记录存在为前提，重复调用 no-op |
| **取消与核销竞争** | 释放发生在订单事务内（cancel / confirmRefund），与核销（createOrder 事务）天然串行于订单维度 |

### 3.6 下单与预览链路接入

| 接入点 | 改动 |
|--------|------|
| `OrderService::createOrder()` | 增加 `?int $couponUserId = null` 参数，事务内（订单创建后）调 `applyToOrder()` |
| `OrderService::createOrders()` | 接收 `?int $couponUserId`，校验券租户 ∈ 本次拆单租户集合，透传至对应子订单（见 3.2） |
| `CartController::createFromCart()` | 请求增加 `coupon_user_id`（可选），传入 createOrders |
| `OrderController`（立即购买） | 同上，传入 createOrder |
| `CartController::preview()` | 请求增加 `coupon_user_id`（可选），调 `previewDiscount()`，返回 `coupon_discount` 与扣减后 `payable_amount` |
| `OrderController::preview()`（立购预览） | 同上 |
| 新增「结算可用券」接口 | `GET /campaign/coupons/available`，入参 `items: [{sku_id, qty}]`，返回可用券 + 预估抵扣（见 3.6.1） |
| `CheckoutResource` / `OrderPreviewResource` / `OrderResource` | 增加 `coupon_discount` 字段 |

> **字段口径注意**：预览资源的 `total_amount` 现为**商品总额**（不含运费），与 `Order::total_amount`（商品 + 运费 − 抵扣）同名字段不同口径。引入券后统一为：预览返回 `goods_amount`（商品总额）、`coupon_discount`、`freight`、`payable_amount = goods_amount + freight − coupon_discount`，避免同名歧义（涉及 `CheckoutResource` / `OrderPreviewResource` 字段重命名，前端联调同步）。
| 请求校验 | `OrderFromCartRequest` / `OrderRequest` / `CheckoutPreviewRequest` / `OrderPreviewRequest` 增加 `coupon_user_id`（`nullable` + `ValidCouponUserRule`） |

**核销时机**：下单即核销（占用），而非支付成功后——避免支付回调链路再处理券；未支付订单取消时释放（见 3.7）。

#### 3.6.1 可用券接口设计

```
GET /campaign/coupons/available
请求：{ "items": [{ "sku_id": 1, "qty": 2 }, ...] }

响应（按租户分组）：
{
  "data": [
    {
      "tenant_id": 1,
      "coupons": [
        {
          "coupon_user_id": 88,
          "name": "满100减20",
          "type": { "value": "fixed", "label": "固定金额" },
          "discount_preview": "20.00",        // 预估抵扣金额
          "min_amount": "100.00",
          "base_amount": "156.00",             // 该券的抵扣基数
          "applicable": true,
          "expired_at": "2026-10-01 00:00:00"
        },
        { "coupon_user_id": 92, "applicable": false, "inapplicable_reason": "订单金额未满足使用条件" }
      ]
    }
  ]
}
```

- 仅返回当前用户持有、未使用、未过期、且券定义 isValid 的券实例；不可用的也返回（带原因），供前端置灰展示
- 预估抵扣用 `previewDiscount()`，与下单同一路径
- 入参商品按租户分组后逐租户判定（跨店购物车每组独立）
- 取价轻量构造：可用性预估不经过 `OrderItemDto` 完整校验（避免库存不足导致整个接口失败），直接按 SKU 单价计算小计；完整校验留给下单时点

### 3.7 取消、退款返还与退款金额分摊

#### 3.7.1 取消返还

`OrderService::cancel()`（仅 Pending 可取消，尚未支付）事务内调 `releaseFromOrder()`。

#### 3.7.2 全额退款返还（已确认：部分退款不返还券）

`RefundService::confirmRefund()` 完成退款后，`updateOrderStatusAfterRefund()` 判定 `allRefunded`（全部订单项数量已退）时调 `releaseFromOrder()`。部分退款不返还券——券作用于整单基数，部分退款时券保持已使用状态。

#### 3.7.3 退款金额分摊（资损防护，必做，已确认：退款以实付金额为准）

**问题**：`calculateRefundAmount()` 按订单项单价 × 数量计算，与实付无关。引入券抵扣后，实付 = amount − coupon_discount + freight，但全额退款会退 amount + freight，**超出实付**。

**已确认决策：退款以实付金额为准。** 券抵扣按比例分摊到订单项，可退金额 = 分摊后金额；任何退款组合总额不得超过实付。

```
分摊比例 = coupon_discount ÷ amount（商品总额，不含运费）
每订单项分摊抵扣 = coupon_discount × (项小计 ÷ amount)，四舍五入 2 位
累积误差 = coupon_discount − Σ(各项分摊)，计入金额最大的项（保证 Σ分摊 = coupon_discount）
退款项可退商品金额 = 项小计 − 对应分摊（未用券订单分摊为 0，行为与现状一致）
```

- `calculateRefundAmount()` 的 `goodsAmount` 改为按分摊后金额累加；`RefundData` 校验逻辑不变
- `refund_items.price` 仍存订单项成交单价（快照不变），分摊明细通过 `refund.total = goods_amount + freight_amount` 体现；`goods_amount` 即分摊后总额
- 部分数量退款：按该项分摊后单价 × 可退数量折算（`项分摊 ÷ 项数量` 得分摊后单价，四舍五入 2 位，剩余数量可退金额尾差在最后一次退清时 clamp ≤ 剩余可退）
- 运费退还逻辑不变（券不作用于运费），但**新增运费退还上限**（见下方连带修复）
- **兜底校验**：任何退款单创建时 `Σ(该订单全部退款单 total) ≤ 实付 total_amount`，超出则拒绝——防止分摊边界或异常数据导致超退

> **连带修复：运费重复退还**。现状 `calculateRefundAmount()` 对 OnlyRefund 每笔退**全额运费**，未发货订单可分多笔部分退款 → 运费被重复退还（现状已有的漏洞，引入「Σ退款 ≤ 实付」兜底后会直接整笔拒绝第二笔，剩余商品反而退不掉）。因此运费退还同步加上限：`Σ(该订单全部退款单已退运费) + 本笔运费 ≤ 订单运费`，本笔运费 = 剩余可退运费。与商品分摊共同保证：任何退款组合总额 ≤ 实付。

> 分摊比例基于 `amount`（商品总额），与身份折扣无关：身份折扣已体现在订单项单价里，分摊只处理订单级券抵扣。

#### 3.7.4 退款分摊示例

订单：商品 A 小计 60、商品 B 小计 40，券抵扣 20（fixed），实付商品金额 = 80 + 运费。

| 项 | 小计 | 分摊 = 20 × 小计/100 | 可退商品金额 |
|----|------|---------------------|--------------|
| A | 60 | 12 | 48 |
| B | 40 | 8 | 32 |

- 仅退 A（全数量）：退 48 + 运费规则；券不返还（部分退款）
- A、B 全退：退 80 + 运费，券返还（`allRefunded`）

### 3.8 抽奖券奖品闭环

`LotteryService::fulfillPrize()` 扩展支持 `LotteryPrizeType::Coupon`：

- 兑奖时读取 `prize_config` 中的 `coupon_id`，调 `CouponService::sendToUser()` 发放；
- 发放成功后 `PrizeRecord → Fulfilled`；
- 券发放失败（券停用 / 已达上限）→ 抛异常保持 Pending，供人工处理（换券或取消奖品）。

> `PrizesRelationManager` 表单已有 Coupon 类型下的券选择器（`visible(type === Coupon)`），前台无需改动；`prize_config` 的券字段格式在 C4 实现时对齐确认。

### 3.9 死代码清理

- `Coupon::canUserUse()`：删除（`sendToUser()` 内已实现更精确的检查）
- `ValidCouponRule`：删除，新增 `ValidCouponUserRule`（校验 coupon_user_id：存在、归属当前用户、未使用、未过期），供下单/预览请求使用
- **移除 `coupons.code` 字段（已确认）**，涉及 8 处：

| 文件 | 改动 |
|------|------|
| `database/migrations/0003_03_00_000001_create_coupons_table.php` | 删除 code 字段定义（直接改源文件） |
| `database/factories/Campaign/CouponFactory.php` | 删除 code 定义 |
| `app/Filament/{Tenant,Backend}/Clusters/Campaign/Resources/Coupons/Schemas/CouponForm.php` | 删除表单项 |
| `app/Filament/{Tenant,Backend}/Clusters/Campaign/Resources/Coupons/Schemas/CouponInfolist.php` | 删除详情项 |
| `app/Filament/{Tenant,Backend}/Clusters/Campaign/Resources/Coupons/Tables/CouponsTable.php` | 删除列表列 |

> 迁移为改源文件 + 全量重建，code 字段直接删除，API 资源 `CouponResource` 未输出 code，前端无影响。

### 3.10 后台展示

| 位置 | 改动 |
|------|------|
| Tenant / Backend 订单表格与详情（`OrdersTable` / `OrderInfolist`） | 增加「优惠券抵扣」金额展示（仅 `coupon_discount > 0` 时显示），实付金额按新口径自动生效 |
| 券详情 `OrdersRelationManager` | 展示 `coupon_order.pivot.discount_amount`（抵扣金额列） |
| `CampaignStatsWidget` | 无需改动（按 `CouponOrder::sum('discount_amount')` 统计，核销落库后自动有数） |

---

## 四、边界场景

| 场景 | 行为 |
|------|------|
| fixed 券面额 ≥ 商品基数 | 抵扣 clamp 至 `amount − 0.01`，**商品实付最低 0.01，必须走支付**（见 3.3.2） |
| percent 券无 max_discount | 不封顶，抵扣 = 基数 × value%，同样受 0.01 最低实付 clamp |
| 限定商品券的基数 < 订单商品总额 | 抵扣上限 = min(基数, amount − 0.01)，两层约束独立生效（见 3.3.2） |
| 未发货订单分多笔 OnlyRefund | 每笔运费退还受「Σ已退运费 ≤ 订单运费」上限约束，首笔退全额、后续笔为 0（见 3.7.3） |
| min_amount = 0 / null | 不做门槛判断 |
| 券租户 ≠ 商品租户 | 拒绝整单：「优惠券不适用于所选商品」 |
| 下单后券定义被禁用/删除 | 已核销不回退（以下单时点校验为准）；软删除券的已有 coupon_order 记录不受影响 |
| 取消时券实例已过期 | 仍恢复 `is_used=false`，过期态由 `expired_at` 表达 |
| 同一券并发两笔订单 | 乐观更新仅一笔成功，另一笔回滚提示「优惠券已被使用」 |
| 重复释放（取消两次/退款重复确认） | `releaseFromOrder()` 幂等 no-op |
| 抵扣后实付被 clamp 至 0.01 的订单 | 正常走现有支付流程（0.01 元支付），无需 0 元支付特殊分支 |
| 退款单金额超出实付 | 兜底校验拒绝创建 |

---

## 五、任务拆分

按依赖关系拆为 4 个可独立评审的子任务：

| 子任务 | 内容 | 依赖 | 关键交付 |
|--------|------|------|----------|
| **C1 金额口径** | orders 加 `coupon_discount`、`Order::getTotalAmount()` 调整、订单资源/后台展示同步 | 无 | 金额口径支持抵扣，不带券订单（coupon_discount=0）行为与现状一致 |
| **C2 核销服务** | `CouponService` 改造：bcmath 化、`baseAmountFor` / `previewDiscount` / `applyToOrder` / `releaseFromOrder`、发放加锁、过期券定时清理命令、死代码清理 | C1 | 核销/释放原子能力就绪，过期券自动清理 |
| **C3 链路接入** | 下单/预览/立购接入券参数、可用券接口、请求校验改造 | C2 | 结算页选券、下单抵扣、预览与下单金额一致 |
| **C4 返还与闭环** | 取消/全额退款返还券、退款金额分摊与超退兜底、运费退还上限、抽奖 Coupon 奖品兑奖发放 | C2 | 券生命周期与退款安全闭环 |

## 六、测试用例清单

### C2 核销服务（`tests/Feature/Campaign/CouponServiceTest.php` 扩展）

- calculateDiscount bcmath：fixed 面额 > 基数取基数；percent 封顶/不封顶；min_amount 边界（等于/差 0.01）
- 最低实付 clamp：fixed 券面额 ≥ 商品总额 → 抵扣 = amount − 0.01，实付 0.01；percent 大折扣同理；预览与下单 clamp 结果一致
- baseAmountFor：全场券、限定商品券（部分命中/全部命中/未命中 null）、非 Sku 订单项排除
- applyToOrder：正常核销（三表写入断言）；非本人券/已使用/已过期/券停用/租户不匹配 → 异常
- releaseFromOrder：正常释放（is_used 复位、coupon_discount 归零）；无记录 no-op；过期券释放
- sendToUser 并发：两进程同时领最后 1 张，仅 1 成功（锁验证）
- 过期清理：过期未使用券被删除；已核销券保留；未过期券保留；清理后限领额度释放（可重新领取）

### C3 链路接入（`tests/Feature/Mall/`）

- 购物车下单带券：订单金额 = 折后商品 + 运费 − 券；coupon_order / is_used 断言
- 立即购买带券：同上
- 预览带券返回 coupon_discount 与下单一致（同输入两次调用断言相等）
- 跨店拆单带券：券只抵扣所属租户子订单；券租户不在拆单集合 → 拒绝
- 可用券接口：返回分组、不可用券带原因
- 同券并发下单：仅一笔成功

### C4 返还与分摊（`tests/Feature/Mall/Refund*` 扩展）

- 分摊计算：两商品订单按比例分摊（含误差归位断言 Σ分摊 = coupon_discount）
- 运费退还上限：未发货订单两笔 OnlyRefund，首笔退全额运费、第二笔运费为 0（修复现状重复退运费）
- 部分退款不返还券；全额退款返还券
- 未支付取消返还券
- 超退兜底：构造多笔退款超出实付 → 拒绝
- 抽奖 Coupon 奖品兑奖 → 券出现在用户持券列表

### 回归

- 现有 17 个 CouponServiceTest 用例随签名调整改造后全过
- 不带券下单/支付/结算/退款/发票全链路回归（coupon_discount=0 行为与现状一致）

## 七、验收标准

- **核销**：下单传有效 `coupon_user_id` → 订单实付 = 商品折后总额 + 运费 − 券抵扣，`coupon_order` 落库，`is_used=true`；金额不足 / 非适用商品 / 已使用 / 已过期 / 非本人 / 租户不匹配 → 拒绝并提示
- **最低实付**：抵扣导致实付不足 0.01 时 clamp 至 0.01，订单正常走支付流程，无 0 元订单分支
- **一致性**：结算预览与下单的 `coupon_discount` 完全一致；预览返回可用的券下单必成功（并发窗口内被他人使用除外）
- **幂等并发**：同一券并发两笔订单，仅一笔成功；重复提交订单不重复扣券
- **发放**：并发领取不突破 `usage_limit` / `usage_limit_per_user`
- **过期清理**：过期未使用券被物理删除，已核销记录保留，清理幂等（重复执行无副作用）
- **返还**：未支付取消、全额退款后券恢复可用；部分退款不返还；重复释放无副作用
- **退款安全**：任何退款组合总额 ≤ 实付（分摊 + 兜底双保险）
- **叠加**：身份折扣 + 优惠券同时生效时，券基数按折后单价计算
- **抽奖**：Coupon 奖品兑奖后券出现在用户「我的券」中
- **回归**：不带券的既有链路（下单/支付/结算/退款/发票）不受影响
- 全部改动通过 Pint 与静态检查

## 八、涉及文件清单

| 子任务 | 文件 | 改动 |
|--------|------|------|
| C1 | `database/migrations/0003_02_00_000001_create_orders_table.php` | orders 加 `coupon_discount`（直接改源文件） |
| C1 | `app/Models/Mall/Order.php` | `getTotalAmount()` 扣减、casts、`coupons()` 关联 |
| C1 | `app/Http/Resources/Mall/Order*Resource.php` | `coupon_discount` 字段 |
| C1 | `app/Filament/{Tenant,Backend}/Clusters/Mall/Resources/Orders/` | 表格/详情抵扣展示 |
| C2 | `app/Services/Campaign/CouponService.php` | calculateDiscount 改造 + baseAmountFor / previewDiscount / applyToOrder / releaseFromOrder + 发放加锁 |
| C2 | `app/Models/Campaign/Coupon.php` | 删除 `canUserUse()` |
| C2 | `database/migrations/0003_03_00_000001_create_coupons_table.php` | 删除 `code` 字段（直接改源文件） |
| C2 | `database/factories/Campaign/CouponFactory.php` | 删除 `code` 定义 |
| C2 | `app/Filament/{Tenant,Backend}/Clusters/Campaign/Resources/Coupons/`（Form / Infolist / Table） | 删除 `code` 表单项 / 详情项 / 列表列 |
| C2 | `app/Console/Commands/Campaign/CouponExpireCommand.php` | 新增 `app:campaign:coupon-expire` 过期券清理命令 |
| C2 | `routes/console.php` | 注册每日清理调度 |
| C2 | `app/Rules/Campaign/ValidCouponUserRule.php` | 新增（替代 ValidCouponRule） |
| C3 | `app/Services/Mall/OrderService.php` | createOrder / createOrders 券参数与核销调用 |
| C3 | `app/Http/Controllers/Mall/CartController.php` / `OrderController.php` | 预览与下单接入 |
| C3 | `app/Http/Controllers/Campaign/CouponController.php` + `routes/apis/campaign.php` | available 接口 |
| C3 | `app/Http/Requests/Mall/*Request.php` | `coupon_user_id` 校验 |
| C3 | `app/Http/Resources/Mall/CheckoutResource.php` / `OrderPreviewResource.php` | 抵扣字段 |
| C4 | `app/Services/Mall/OrderService.php`（cancel） | 释放券 |
| C4 | `app/Services/Mall/RefundService.php` | 退款金额分摊 + 运费退还上限 + 超退兜底 + 全退返还券 |
| C4 | `app/Services/Campaign/LotteryService.php` | fulfillPrize 支持 Coupon |
| C4 | `app/Filament/*/Clusters/Campaign/Resources/Coupons/RelationManagers/OrdersRelationManager.php` | 抵扣金额列 |
| 全部 | `tests/Feature/Campaign/` / `tests/Feature/Mall/` | 见测试用例清单 |

## 九、决策记录

> 已确认决策（2026-09-11）：
> - **退款以实付金额为准**（按比例分摊到订单项 + 超退兜底，见 3.7.3）
> - **部分退款不返还券**（仅全额退款返还，见 3.7.2）
> - **一单一券**（见 3.1）
> - **抵扣不用于运费**（见 3.4）
> - **移除 `coupons.code`**（见 3.9）
> - **券过期由定时任务物理清理**（见 3.3.5）
> - **不考虑存量数据兼容**（迁移为改源文件 + 全量重建模式，见 3.1 / 3.9）
> - **最低实付 0.01，必须走支付**（见 3.3.2）

所有决策已确认，无待确认项。

---

## 十、复核遗留：实现时需细化的设计点

> 2026-09-11 复核结论：方案整体成立，以下 5 点不影响方案结构，但实现前需补充口径，避免 C 阶段返工。

### 10.1 可用券接口的取价口径（关联 3.6.1 与 3.3.2 的矛盾）

3.6.1 称可用券接口「取价轻量构造、不经过 `OrderItemDto` 完整校验」，但 3.3.2 / 七 承诺「预览与下单同路径、金额一致」。身份折扣是**读时施加**（`ProductDiscountService::priceFor/applyPercent` → `OrderItemDto::$price`），轻量构造若直接用 SKU 原价，身份用户的 `base_amount` / `discount_preview` 会与下单不一致，破坏「预览可用的券下单必成功且金额一致」的验收标准。

**细化**：轻量构造跳过的仅是**库存/可售校验**；**单价必须与下单同源**——继续经 `ProductDiscountService` 取折后单价计算小计（可复用 `percentForProducts` 批量接口避免 N+1）。

### 10.2 percent 分支的舍入口径（3.3.1）

`capPercentDiscount` 若用 `bcdiv($baseAmount × value, 100, 2)`，bcmath 是**截断**而非四舍五入：`33.33 × 15%` 得 `4.99` 而非 `5.00`。预览与下单同路径可保证自洽，但与用户直觉（百分比心算）会有 0.01 争议，且需与项目其它金额舍入规则对齐。

**细化**：显式定义 percent 计算的舍入方式（建议与 `ProductDiscountService::applyPercent` 现行口径一致），写入 `calculateDiscount` 的实现与测试用例（补一条非整除断言，如 `33.33 × 15%`）。

### 10.3 clamp 表达式统一为 bcmath（3.3.2）

3.3.2 伪代码 `$discount = min($discount, bcsub($orderAmount, '0.01', 2))` 用了 `min()` 对十进制字符串做数值比较。功能正确，但与全链路 bcmath 纪律不一致（金额判定均用 `bccomp`）。

**细化**：实现时改用 `bccomp` 判定取小，`min()` 版本从文档删除，避免照抄。

### 10.4 `previewDiscount` 返回类型注解笔误（3.3.2）

`@return string{base_amount: string, discount: string}` 应为 `array{base_amount: string, discount: string}`。实现时更正即可，此处留档防止照抄 PHPDoc。

### 10.5 预览字段改名的破坏性提示（3.6）

`total_amount → goods_amount` 消除了与订单同名异义的冲突，但改后**同概念两套命名**并存：预览用 `goods_amount` / `payable_amount`，订单用 `amount` / `total_amount`。属可接受的上下文差异，但需注意：

- 这是**破坏性变更**：`CheckoutResource` / `OrderPreviewResource` 的 `total_amount` 字段直接消失，前端所有引用处需同步（文档已提「前端联调同步」，实现时在 PR/联调说明中再次显式标注 breaking change）；
- 前端需维护字段映射（预览 `goods_amount` ↔ 订单 `amount`、预览 `payable_amount` ↔ 订单 `total_amount`），建议在 API 文档中给出对照表。
