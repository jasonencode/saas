# 购物车模块设计文档

## 📊 数据库表结构设计

### carts 表 - 购物车主表

| 字段名 | 类型 | 说明 | 备注 |
|--------|------|------|------|
| id | bigint | 主键 ID | 自增 |
| user_id | bigint | 用户 ID | 登录用户标识 |
| session_id | string(255) | 会话 ID | 未登录用户标识（可为空） |
| status | boolean | 状态 | `easyStatus()` 启停 |
| created_at | timestamp | 创建时间 | |
| updated_at | timestamp | 更新时间 | |
| deleted_at | timestamp | 删除时间 | 软删除 |

#### 索引设计

```php
// 唯一索引：每个登录用户只有一个购物车（跨店）
$table->unique('user_id');

// 唯一索引：每个会话（未登录）只有一个购物车
$table->unique('session_id');

// 辅助索引
$table->index(['user_id', 'status']);
```

### cart_items 表 - 购物车商品项表

| 字段名 | 类型 | 说明 | 备注 |
|--------|------|------|------|
| id | bigint | 主键 ID | 自增 |
| cart_id | bigint | 购物车 ID | 外键，级联删除 |
| product_id | bigint | 商品 ID | 冗余，便于查询 |
| sku_id | bigint | SKU ID | 商品规格 ID |
| qty | unsigned int | 购买数量 | 默认 1 |
| price_at_add | decimal(12,2) | 加入购物车时单价 | 价格快照 |
| created_at | timestamp | 创建时间 | |
| updated_at | timestamp | 更新时间 | |

#### 索引设计

```php
// 外键索引
$table->foreignId('cart_id')
    ->index()
    ->constrained()
    ->onDelete('cascade');

// 商品 / SKU 查询索引
$table->unsignedBigInteger('product_id')->index();
$table->unsignedBigInteger('sku_id')->index();

// 唯一索引：同一个购物车内同一 SKU 只能有一条记录
$table->unique(['cart_id', 'sku_id']);
```

---

## ✅ 设计优点分析

### 1. 双轨制用户支持
- **登录用户**：通过 `user_id` 关联
- **未登录用户**：通过 `session_id` 标识（预留，当前 API 全部需登录）
- **平滑过渡**：用户登录后可以合并 session 购物车

### 2. 跨店购物车（用户级）
- 购物车不区分租户，同一用户全局一张购物车，可包含多家店铺的商品
- 店铺归属经商品（`cart_items.product_id`）隐式确定
- 下单时按租户拆分订单（`OrderService::createOrders`），每店一单

### 3. 价格快照机制
- `price_at_add` 记录加入购物车时的价格
- 避免后续商品调价影响购物车结算
- 保留历史交易信息

### 4. 索引优化合理
- 唯一索引防止重复数据
- 复合索引优化常用查询场景

---

## 🎯 模型实现

### Cart 模型

```php
// app/Models/Mall/Cart.php
namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\HasEasyStatus;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
class Cart extends Model
{
    use BelongsToTenant,
        BelongsToUser,
        HasEasyStatus,
        SoftDeletes;

    /**
     * 获取购物车商品总数
     */
    public function getTotalQtyAttribute(): int
    {
        return $this->items->sum('qty');
    }

    /**
     * 获取购物车总金额
     */
    public function getTotalAmountAttribute(): float
    {
        return (float) $this->items->sum(function (CartItem $item) {
            return $item->qty * $item->price_at_add;
        });
    }

    /**
     * 检查购物车是否为空
     */
    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    /**
     * 清空购物车
     */
    public function clear(): void
    {
        $this->items()->delete();
    }

    /**
     * 购物车商品项
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * 检查购物车是否过期
     */
    public function isExpired(): bool
    {
        return $this->expired_at && $this->expired_at->isPast();
    }
}
```

### CartItem 模型

```php
// app/Models/Mall/CartItem.php
namespace App\Models\Mall;

use App\Enums\Mall\ProductStatus;
use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
class CartItem extends Model
{
    protected function casts(): array
    {
        return [
            'price_at_add' => 'decimal:2',
        ];
    }

    /**
     * 关联购物车
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * 关联 SKU
     */
    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    /**
     * 关联商品
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 小计金额
     */
    public function getSubTotalAttribute(): string
    {
        return bcmul((string) $this->qty, (string) $this->price_at_add, 2);
    }

    /**
     * 检查商品是否可购买
     */
    public function isAvailable(): bool
    {
        return $this->product &&
            $this->product->status === ProductStatus::Up &&
            $this->sku &&
            $this->sku->stock >= $this->qty;
    }
}
```

---

## 🔌 API 接口文档

### 基础信息

- **基础路径**: `/mall/cart`
- **认证方式**: Bearer Token (auth:sanctum)
- **数据格式**: JSON
- **路由文件**: `routes/apis/mall.php`

---

### 1. 获取购物车列表

**请求：**
```http
GET /mall/cart
Authorization: Bearer {token}
```

**响应示例：**（`CartResource`）
```json
{
  "cart_id": 1,
  "items": [
    {
      "item_id": 10,
      "product": {
        "product_id": 100,
        "name": "商品名称",
        "cover": "/images/cover.jpg",
        "fulfillment_types": ["mail", "pickup"]
      },
      "sku": {
        "sku_id": 1000,
        "name": "红色 XL"
      },
      "qty": 2,
      "price": "99.00",
      "sub_total": "198.00",
      "is_available": true
    }
  ],
  "total_qty": 2,
  "total_amount": 198.00
}
```

**说明：**
- 购物车不存在时自动创建（`CartService::getOrCreateCart`）
- 自动加载商品和 SKU 信息
- 包含可用性和履约方式

---

### 2. 添加商品到购物车

**请求：**
```http
POST /mall/cart/add
Authorization: Bearer {token}
Content-Type: application/json

{
  "sku_id": 1000,
  "qty": 2
}
```

**验证规则：**（`StoreCartItemRequest`）
- `sku_id`: required, integer, exists:skus,id
- `qty`: required, integer, min:1

**业务逻辑：**
1. 获取或创建购物车
2. 如果 SKU 已存在则累加数量（`cart_id + sku_id` 唯一索引）
3. 使用事务保证数据一致性

**响应：** 同购物车列表结构（message：`添加成功`）。

**可能错误：**
- SKU 不存在
- 商品库存不足
- 购买数量超过限制

---

### 3. 结算预览

**请求：**
```http
POST /mall/cart/preview
Authorization: Bearer {token}
Content-Type: application/json

{
  "item_ids": [10, 11],
  "fulfillment_type": "mail",
  "address_id": 1
}
```

**业务逻辑：**
1. 校验所选履约方式（`FulfillmentType`）被所有商品支持，任一不支持则拒绝
2. 计算商品总金额（bcmath 精度运算）
3. 快递邮寄（`mail`）履约方式按运费模板计费（`DeliveryService::calculateOrderFreight`）；门店自提 / 虚拟商品免运费
4. 返回用户地址列表供选择

**响应示例：**（`CheckoutResource`）
```json
{
  "items": [ ... ],
  "addresses": [ ... ],
  "address": { ... },
  "total_amount": "198.00",
  "freight": "10.00",
  "payable_amount": "208.00"
}
```

---

### 4. 从购物车下单结算

**请求：**
```http
POST /mall/cart/checkout
Authorization: Bearer {token}
Content-Type: application/json

{
  "item_ids": [10, 11],
  "fulfillment_type": "mail",
  "address_id": 1,
  "pickup_point_id": null
}
```

**业务逻辑：**
1. 用户级缓存锁（`mall_order_{userId}`，30 秒）防止重复提交
2. 将购物车项转换为 `OrderItemDto`
3. 调用 `OrderService::createOrders()` 创建订单（按配送方式拆单）
4. 清理已下单的购物车商品

**响应：** `{"code": 0, "message": "订单创建成功", "data": [订单ID列表]}`（201）。

**可能错误：**
- `请勿重复提交订单`（锁未获取）
- 未找到有效的购物车商品

---

### 5. 更新商品数量

**请求：**
```http
PUT /mall/cart/items/{item}
Authorization: Bearer {token}
Content-Type: application/json

{
  "qty": 5
}
```

**说明：**
- 只能更新自己购物车中的商品（越权返回 403）
- 数量范围见 `UpdateCartItemRequest`

---

### 6. 删除购物车商品

**请求：**
```http
DELETE /mall/cart/items/{item}
Authorization: Bearer {token}
```

**说明：**
- 只删除单个商品项，越权返回 403
- 不影响其他商品

---

### 7. 清空购物车

**请求：**
```http
POST /mall/cart/clear
Authorization: Bearer {token}
```

**响应示例：**
```json
{
  "cart_id": 1,
  "items": [],
  "total_qty": 0,
  "total_amount": 0.00
}
```

---

## 🔒 安全与业务逻辑

### 1. 权限控制
- 所有接口都需要登录认证
- 用户只能操作自己的购物车（`$item->cart->user_id !== Auth::id()` 时返回 403）
- 越权操作返回 403 Forbidden

### 2. 数据一致性
- 添加/更新操作使用数据库事务（`CartService`）
- 异常情况自动回滚
- 下单接口使用缓存锁防重复提交

### 3. 库存验证
- 添加商品时检查库存
- 更新数量时重新验证库存
- 库存不足时拒绝操作

### 4. 价格保护
- 使用 `price_at_add` 记录入库价格
- 不受商品后续调价影响
- 保障用户权益

### 5. 金额精度
- 金额运算统一使用 bcmath（`bcmul` / `bcadd`）
- `price_at_add` 以 decimal 字符串返回

---

## 💡 扩展建议

### 1. Redis 缓存优化
```php
// 将活跃用户的购物车缓存在 Redis 中
$cartData = Cache::remember(
    "user_cart_{$userId}",
    3600,
    fn() => $cart->load('items')->toArray()
);
```

**优势：**
- 减少数据库查询
- 提升响应速度
- 适合高频访问场景

### 2. 购物车合并逻辑
```php
/**
 * 用户登录后合并 session 购物车
 */
public function mergeSessionCart(string $sessionId): void
{
    $sessionCart = Cart::where('session_id', $sessionId)->first();

    if ($sessionCart) {
        foreach ($sessionCart->items as $item) {
            $this->addItem($item->sku_id, $item->qty);
        }

        $sessionCart->delete();
    }
}
```

### 3. 降价提醒功能
```php
// 监听商品价格变化事件（ProductChanged）
Event::listen(ProductChanged::class, function ($event) {
    // 查询受影响购物车项并发送通知
});
```

---

## 📈 性能优化建议

### 1. 查询优化
```php
// 使用延迟加载避免 N+1 问题
$cart->load(['items.product', 'items.sku']);

// 只查询部分商品
$cartItems = $cart->items()
    ->whereIn('id', $itemIds)
    ->get();
```

### 2. 批量操作
```php
// 批量删除已下单商品
$cart->items()->whereIn('id', $itemIds)->delete();
```

### 3. 懒加载边界
```php
// 在 Resource 中使用 whenLoaded 避免多余查询
public function toArray(Request $request): array
{
    return [
        'items' => CartItemResource::collection(
            $this->whenLoaded('items')
        ),
    ];
}
```

---

## 📝 版本历史

| 版本 | 日期 | 变更说明 |
|------|------|----------|
| 1.0 | 2026-03-27 | 初始版本，完成基础购物车功能 |
| 1.1 | 2026-08-28 | 新增结算预览（preview）、从购物车下单（checkout）、履约方式校验；移除不存在的 toggle 接口 |

---

## 📚 相关文档

- [API 总览](api.md)
- [模型定义](models.md)
- [订单模块 API 文档](../../../docs/apis/mall.md)（仓库根目录 `docs/apis/` 开发文档）
