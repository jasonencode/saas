# 统一订单系统设计方案

> 创建时间：2026-06-15
> 状态：设计中

---

## 一、设计目标

1. 使用统一的 `Order` 模型管理所有订单类型
2. 支持不同订单类型的差异化处理
3. 保持向后兼容，平滑迁移

---

## 二、数据库设计

### 2.1 orders 表修改

```sql
ALTER TABLE orders
    ADD COLUMN order_type VARCHAR(32) NOT NULL DEFAULT 'product' AFTER tenant_id COMMENT '订单类型',
    ADD COLUMN payable_id BIGINT AFTER order_type COMMENT '关联对象ID（商品/身份等）',
    ADD COLUMN payable_type VARCHAR(255) AFTER payable_id COMMENT '关联对象类型',
    ADD INDEX idx_order_type (order_type),
    ADD INDEX idx_payable (payable_type, payable_id);
```

**订单类型枚举：**
```php
enum OrderType: string
{
    case Product = 'product';   // 商品订单
    case Identity = 'identity'; // 身份订单
    // 可扩展：case Course = 'course'; // 课程订单
}
```

### 2.2 identity_orders 表迁移

```sql
-- 步骤1：数据迁移到 orders 表
INSERT INTO orders (
    tenant_id, no, user_id, order_type, 
    payable_id, payable_type, amount, status, 
    created_at, updated_at
)
SELECT 
    tenant_id, no, user_id, 'identity',
    identity_id, 'App\\Models\\User\\Identity', amount, status,
    created_at, updated_at
FROM identity_orders;

-- 步骤2：创建 order_items 记录（身份订单作为单个订单项）
INSERT INTO order_items (order_id, product_snapshot_id, sku_snapshot_id, price, qty)
SELECT 
    o.id,
    NULL, -- 身份订单不需要商品快照
    NULL,
    io.amount / io.qty, -- 单价
    io.qty
FROM identity_orders io
JOIN orders o ON o.payable_id = io.identity_id AND o.order_type = 'identity';

-- 步骤3：删除 identity_orders 表（迁移完成后）
DROP TABLE identity_orders;
```

---

## 三、模型设计

### 3.1 OrderType 枚举

```php
<?php

namespace App\Enums\Mall;

enum OrderType: string
{
    case Product = 'product';
    case Identity = 'identity';

    public function label(): string
    {
        return match ($this) {
            self::Product => '商品订单',
            self::Identity => '身份订单',
        };
    }
}
```

### 3.2 Order 模型修改

```php
<?php

namespace App\Models\Mall;

use App\Enums\Mall\OrderType;
use App\Models\User\Identity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Order extends Model
{
    // ... 现有代码 ...

    protected $casts = [
        // ... 现有 casts ...
        'order_type' => OrderType::class,
    ];

    /**
     * 关联可支付对象（多态关联）
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 判断是否为商品订单
     */
    public function isProductOrder(): bool
    {
        return $this->order_type === OrderType::Product;
    }

    /**
     * 判断是否为身份订单
     */
    public function isIdentityOrder(): bool
    {
        return $this->order_type === OrderType::Identity;
    }

    /**
     * 获取订单标题（支付展示用）
     */
    public function getTitleAttribute(): string
    {
        return match ($this->order_type) {
            OrderType::Product => sprintf('[商城订单]:%s', $this->no),
            OrderType::Identity => sprintf('[身份订单]:%s', $this->no),
            default => sprintf('[订单]:%s', $this->no),
        };
    }

    /**
     * 获取订单商品数量（兼容身份订单）
     */
    public function getSkusQuantitiesAttribute(): int
    {
        if ($this->isIdentityOrder()) {
            return (int) $this->items->sum('qty');
        }
        
        return (int) $this->items->sum('qty');
    }
}
```

### 3.3 IdentityOrder 模型改造（可选保留）

```php
<?php

namespace App\Models\User;

use App\Enums\Mall\OrderType;
use App\Models\Mall\Order;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @deprecated 使用 Order::where('order_type', 'identity') 替代
 */
class IdentityOrder extends Model
{
    /**
     * 关联统一订单
     */
    public function order(): HasOne
    {
        return $this->hasOne(Order::class, 'payable_id')
            ->where('order_type', OrderType::Identity);
    }
}
```

---

## 四、服务层改造

### 4.1 统一订单服务接口

```php
<?php

namespace App\Services\Mall\Contracts;

use App\Models\Mall\Order;
use App\Models\User\User;

interface OrderServiceInterface
{
    /**
     * 创建订单
     */
    public function create(User $user, array $data): Order;

    /**
     * 取消订单
     */
    public function cancel(Order $order, User $user): Order;

    /**
     * 完成订单
     */
    public function complete(Order $order, User $user): Order;
}
```

### 4.2 商品订单服务

```php
<?php

namespace App\Services\Mall;

use App\Enums\Mall\OrderType;
use App\Models\Mall\Order;
use App\Models\User\User;
use App\Services\Mall\Contracts\OrderServiceInterface;

class ProductOrderService implements OrderServiceInterface
{
    public function create(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $order = Order::create([
                'tenant_id' => tenant('id'),
                'order_type' => OrderType::Product,
                'user_id' => $user->id,
                // ... 其他字段
            ]);

            // 创建订单项（商品快照）
            foreach ($data['items'] as $item) {
                $snapshot = SnapshotService::create($item['product'], $item['sku']);
                $order->items()->create([...]);
            }

            return $order;
        });
    }
    
    // ... 其他方法
}
```

### 4.3 身份订单服务

```php
<?php

namespace App\Services\Mall;

use App\Enums\Mall\OrderType;
use App\Models\Mall\Order;
use App\Models\User\Identity;
use App\Models\User\User;
use App\Services\Mall\Contracts\OrderServiceInterface;

class IdentityOrderService implements OrderServiceInterface
{
    public function create(User $user, Identity $identity, int $qty = 1): Order
    {
        return DB::transaction(function () use ($user, $identity, $qty) {
            $order = Order::create([
                'tenant_id' => tenant('id'),
                'order_type' => OrderType::Identity,
                'payable_id' => $identity->id,
                'payable_type' => Identity::class,
                'user_id' => $user->id,
                'amount' => $identity->price * $qty,
                'status' => OrderStatus::Pending,
            ]);

            // 身份订单也创建 order_item（便于统一统计）
            $order->items()->create([
                'product_name' => $identity->name,
                'price' => $identity->price,
                'qty' => $qty,
            ]);

            return $order;
        });
    }

    public function complete(Order $order, User $user): Order
    {
        // 订单完成后，授予用户身份
        $identity = Identity::find($order->payable_id);
        
        $user->identities()->attach($identity->id, [
            'start_at' => now(),
            'end_at' => now()->addDays($identity->days * $order->items->sum('qty')),
        ]);

        $order->update(['status' => OrderStatus::Completed]);
        
        return $order;
    }
    
    // ... 其他方法
}
```

---

## 五、支付回调统一处理

```php
<?php

namespace App\Services\Mall;

use App\Enums\Mall\OrderType;
use App\Models\Mall\Order;

class PaymentCallbackService
{
    public function handlePaymentSuccess(Order $order)
    {
        match ($order->order_type) {
            OrderType::Product => $this->handleProductOrderPaid($order),
            OrderType::Identity => $this->handleIdentityOrderPaid($order),
        };
    }

    protected function handleProductOrderPaid(Order $order)
    {
        // 商品订单支付成功逻辑
        app(ProductOrderService::class)->onPaid($order);
    }

    protected function handleIdentityOrderPaid(Order $order)
    {
        // 身份订单支付成功逻辑
        app(IdentityOrderService::class)->complete($order, $order->user);
    }
}
```

---

## 六、Filament 后台适配

### 6.1 订单列表显示类型

```php
// Tables/OrdersTable.php
Tables\Columns\TextColumn::make('order_type')
    ->label('订单类型')
    ->badge()
    ->color(fn (OrderType $state) => match ($state) {
        OrderType::Product => 'primary',
        OrderType::Identity => 'success',
    }),
```

### 6.2 根据类型显示不同字段

```php
// Schemas/OrderInfolist.php
Infolists\Components\Section::make('订单信息')
    ->schema(function (Order $order) {
        $schema = [
            Infolists\Components\TextEntry::make('no')->label('订单号'),
            Infolists\Components\TextEntry::make('order_type')->label('类型'),
        ];

        if ($order->isIdentityOrder()) {
            $schema[] = Infolists\Components\TextEntry::make('payable.name')
                ->label('身份名称');
        }

        return $schema;
    }),
```

---

## 七、迁移步骤

### 阶段一：准备（不破坏现有功能）

1. ✅ 创建 `OrderType` 枚举
2. ✅ 给 `orders` 表添加 `order_type`、`payable_id`、`payable_type` 字段
3. ✅ 给现有商品订单设置 `order_type = 'product'`

### 阶段二：服务层改造

4. ✅ 创建 `ProductOrderService`（从现有 OrderService 抽取）
5. ✅ 创建 `IdentityOrderService`
6. ✅ 创建统一的 `PaymentCallbackService`

### 阶段三：数据迁移

7. ✅ 将 `identity_orders` 数据迁移到 `orders` 表
8. ✅ 验证数据一致性
9. ✅ 删除 `identity_orders` 表

### 阶段四：代码清理

10. ✅ 移除旧的 `IdentityOrder` 模型（或标记 deprecated）
11. ✅ 更新 Filament 资源
12. ✅ 更新 API 接口

---

## 八、风险与注意事项

| 风险 | 应对措施 |
|------|---------|
| 数据迁移失败 | 先备份，分批迁移，验证数据 |
| 支付回调混乱 | 统一回调入口，按类型分发 |
| 查询性能下降 | 为 order_type 添加索引 |
| 前端适配 | 保持 API 返回结构兼容 |

---

## 九、待确认问题

1. **IdentityOrder 是否有独立的支付流程？**
   - 如果有，需要统一支付接口

2. **是否需要保留 IdentityOrder 表作为备份？**
   - 建议迁移完成后保留一段时间再删除

3. **其他订单类型（如课程）是否也需要统一？**
   - 如果是，设计时预留扩展点
