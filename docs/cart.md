# 购物车模块设计文档

## 📊 数据库表结构设计

### carts 表 - 购物车主表

| 字段名 | 类型 | 说明 | 备注 |
|--------|------|------|------|
| id | bigint | 主键 ID | 自增 |
| tenant_id | bigint | 租户 ID | 多租户隔离 |
| user_id | bigint | 用户 ID | 登录用户标识 |
| session_id | string(255) | 会话 ID | 未登录用户标识 |
| status | boolean | 状态 | 启用/禁用 |
| expired_at | datetime | 过期时间 | 可为空 |
| created_at | timestamp | 创建时间 |  |
| updated_at | timestamp | 更新时间 |  |
| deleted_at | timestamp | 删除时间 | 软删除 |

#### 索引设计

```php
// 唯一索引：同一租户下每个登录用户只有一个购物车
$table->unique(['tenant_id', 'user_id']);

// 唯一索引：同一租户下每个会话（未登录）只有一个购物车
$table->unique(['tenant_id', 'session_id']);

// 用于定时清理过期购物车
$table->index('expired_at');

// 辅助索引
$table->index(['tenant_id', 'status']);
```

### cart_items 表 - 购物车商品项表

| 字段名 | 类型 | 说明 | 备注 |
|--------|------|------|------|
| id | bigint | 主键 ID | 自增 |
| cart_id | bigint | 购物车 ID | 外键关联 carts.id |
| sku_id | bigint | SKU ID | 商品规格 ID |
| qty | unsigned int | 数量 | 默认 1 |
| price_at_add | decimal(10,2) | 加入购物车时单价 | 价格快照 |
| selected | boolean | 是否选中 | 默认 true |
| created_at | timestamp | 创建时间 |  |

#### 索引设计

```php
// 外键索引
$table->foreignId('cart_id')
    ->index()
    ->constrained()
    ->onDelete('cascade');

// SKU 查询索引
$table->unsignedBigInteger('sku_id')->index();

// 唯一索引：同一个购物车内同一 SKU 只能有一条记录
$table->unique(['cart_id', 'sku_id']);
```

---

## ✅ 设计优点分析

### 1. 双轨制用户支持
- **登录用户**：通过 `user_id` 关联
- **未登录用户**：通过 `session_id` 标识
- **平滑过渡**：用户登录后可以合并 session 购物车

### 2. 多租户数据隔离
- 所有购物车数据都包含 `tenant_id`
- 通过复合唯一索引确保租户间数据独立
- 符合 SaaS 架构要求

### 3. 价格快照机制
- `price_at_add` 记录加入购物车时的价格
- 避免后续商品调价影响购物车结算
- 保留历史交易信息

### 4. 索引优化合理
- 唯一索引防止重复数据
- 复合索引优化常用查询场景
- `expired_at` 索引便于定时任务清理

---

## 🎯 模型实现

### Cart 模型

```php
namespace App\Models;

use App\Models\Mall\CartItem;
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

    protected $casts = [
        'expired_at' => 'datetime',
    ];

    /**
     * 购物车商品项
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

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
        return (float) $this->items->sum(function ($item) {
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
namespace App\Models;

use App\Models\Mall\Cart;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;
use App\Models\Traits\BelongsToOrder;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
class CartItem extends Model
{
    use BelongsToOrder;

    protected $casts = [
        'price_at_add' => 'decimal:2',
        'selected' => 'boolean',
    ];

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
        return $this->belongsTo(Product::class)
            ->withTrashed();
    }

    /**
     * 小计金额
     */
    public function getSubTotalAttribute(): float
    {
        return (float) bcmul($this->qty, $this->price_at_add, 2);
    }

    /**
     * 检查商品是否可购买
     */
    public function isAvailable(): bool
    {
        return $this->product &&
            $this->product->status &&
            $this->sku &&
            $this->sku->stock >= $this->qty;
    }
}
```

---

## 🔌 API 接口文档

### 基础信息

- **基础路径**: `/api/mall/cart`
- **认证方式**: Bearer Token (auth:sanctum)
- **数据格式**: JSON

---

### 1. 获取购物车列表

**请求：**
```http
GET /api/mall/cart
Authorization: Bearer {token}
```

**响应示例：**
```json
{
  "cart_id": 1,
  "items": [
    {
      "item_id": 10,
      "product": {
        "product_id": 100,
        "name": "商品名称",
        "cover": "/images/cover.jpg"
      },
      "sku": {
        "sku_id": 1000,
        "name": "红色 XL",
        "specifications": ["红色", "XL"]
      },
      "qty": 2,
      "price": 99.00,
      "sub_total": 198.00,
      "selected": true,
      "is_available": true
    }
  ],
  "total_qty": 2,
  "total_amount": 198.00,
  "is_expired": false
}
```

**说明：**
- 空购物车返回空数组和 0 值
- 自动加载商品和 SKU 信息
- 包含可用性和选中状态

---

### 2. 添加商品到购物车

**请求：**
```http
POST /api/mall/cart/add
Authorization: Bearer {token}
Content-Type: application/json

{
  "sku_id": 1000,
  "qty": 2
}
```

**验证规则：**
- `sku_id`: required, integer, exists:skus,id
- `qty`: required, integer, min:1, max:9999

**业务逻辑：**
1. 检查库存是否充足
2. 如果购物车不存在则创建
3. 如果 SKU 已存在则累加数量
4. 使用事务保证数据一致性

**可能错误：**
- 商品库存不足
- 购买数量超过限制（单次最多 9999 件）

---

### 3. 更新商品数量

**请求：**
```http
PUT /api/mall/cart/items/{item_id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "qty": 5
}
```

**说明：**
- 只能更新自己购物车中的商品
- 数量范围：1-9999
- 自动重新计算总价

---

### 4. 选中/取消商品

**请求：**
```http
POST /api/mall/cart/items/{item_id}/toggle
Authorization: Bearer {token}
```

**说明：**
- 切换选中状态（true ↔ false）
- 用于结算时筛选商品

---

### 5. 删除购物车商品

**请求：**
```http
DELETE /api/mall/cart/items/{item_id}
Authorization: Bearer {token}
```

**说明：**
- 只删除单个商品项
- 不影响其他商品

---

### 6. 清空购物车

**请求：**
```http
POST /api/mall/cart/clear
Authorization: Bearer {token}
```

**响应示例：**
```json
{
  "cart_id": 1,
  "items": [],
  "total_qty": 0,
  "total_amount": 0.00,
  "is_expired": false
}
```

---

## 🔒 安全与业务逻辑

### 1. 权限控制
- 所有接口都需要登录认证
- 用户只能操作自己的购物车
- 越权操作返回 403 Forbidden

### 2. 数据一致性
- 添加/更新操作使用数据库事务
- 异常情况自动回滚
- 防止并发导致的数据错误

### 3. 库存验证
- 添加商品时检查库存
- 更新数量时重新验证库存
- 库存不足时拒绝操作

### 4. 数量限制
- 单个 SKU 最多 9999 件
- 防止恶意大量囤货
- 超出限制返回错误

### 5. 价格保护
- 使用 `price_at_add` 记录入库价格
- 不受商品后续调价影响
- 保障用户权益

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

### 2. 过期清理任务
```php
// app/Console/Commands/CleanExpiredCarts.php
protected $signature = 'carts:clean-expired';

public function handle()
{
    $count = Cart::where('expired_at', '<=', now())
        ->delete();
    
    $this->info("清理了 {$count} 个过期购物车");
}
```

**定时配置：**
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('carts:clean-expired')
        ->dailyAt('03:00');
}
```

### 3. 购物车合并逻辑
```php
/**
 * 用户登录后合并 session 购物车
 */
public function mergeSessionCart(string $sessionId): void
{
    $sessionCart = Cart::where('session_id', $sessionId)
        ->first();
    
    if ($sessionCart) {
        foreach ($sessionCart->items as $item) {
            $this->addItem($item->sku_id, $item->qty);
        }
        
        $sessionCart->delete();
    }
}
```

### 4. 降价提醒功能
```php
// 监听商品价格变化
Event::listen(ProductPriceChanged::class, function ($event) {
    $affectedCarts = CartItem::where('sku_id', $event->sku->id)
        ->where('price_at_add', '>', $event->newPrice)
        ->with('cart.user')
        ->get();
    
    foreach ($affectedCarts as $cartItem) {
        Notification::send($cartItem->cart->user, new PriceDropNotification(
            $cartItem->product,
            $cartItem->price_at_add,
            $event->newPrice
        ));
    }
});
```

---

## 📈 性能优化建议

### 1. 查询优化
```php
// 使用延迟加载避免 N+1 问题
$cart->load(['items.product', 'items.sku']);

// 只查询选中的商品
$selectedItems = $cart->items()
    ->where('selected', true)
    ->get();
```

### 2. 批量操作
```php
// 批量更新选中状态
CartItem::whereIn('id', $itemIds)
    ->update(['selected' => true]);
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

## 🧪 测试建议

### Feature 测试示例
```php
/** @test */
public function user_can_add_product_to_cart()
{
    $user = User::factory()->create();
    $sku = Sku::factory()->create(['stock' => 10]);

    $response = $this->actingAs($user)
        ->postJson('/api/mall/cart/add', [
            'sku_id' => $sku->id,
            'qty' => 2,
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('total_qty', 2);
    
    $this->assertDatabaseHas('cart_items', [
        'sku_id' => $sku->id,
        'qty' => 2,
    ]);
}
```

---

## 📝 版本历史

| 版本 | 日期 | 变更说明 |
|------|------|----------|
| 1.0 | 2026-03-27 | 初始版本，完成基础购物车功能 |
| 1.1 | TBD | 计划：Redis 缓存、购物车合并、降价提醒 |

---

## 📚 相关文档

- [订单模块 API 文档](./orders.md)
- [商品模块 API 文档](./products.md)
- [优惠券模块 API 文档](./coupons.md)
