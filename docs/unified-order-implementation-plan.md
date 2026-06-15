# 统一订单系统实施计划

> 创建时间：2026-06-15
> 支付流程：统一
> 数据迁移：不需要
> 多态关联：OrderItem（订单项级别）

---

## 实施概览

```
阶段一：基础设施（枚举、字段）
  ├── Step 1: OrderType 枚举
  └── Step 2: order_items 表添加多态字段

阶段二：模型层改造
  ├── Step 3: Order 模型改造
  ├── Step 4: OrderItem 模型改造（核心）
  └── Step 5: Identity 模型关联

阶段三：服务层拆分
  ├── Step 6: OrderServiceInterface 接口
  ├── Step 7: ProductOrderService
  ├── Step 8: IdentityOrderService
  └── Step 9: PaymentCallbackService 统一回调

阶段四：Filament 后台适配
  ├── Step 10: 订单列表显示类型
  └── Step 11: 订单详情按类型显示
```

---

## 核心设计

### 数据库结构

```
orders (订单主表)
├── id, tenant_id, no, user_id
├── order_type          -- 订单类型标识
├── amount, freight, status
└── items() HasMany

order_items (订单项) ← 多态关联在这里
├── id, order_id
├── payable_id          -- 关联对象ID (Product/Sku/Identity)
├── payable_type        -- 关联对象类型
├── product_name        -- 快照：名称
├── cover               -- 快照：封面
├── price, qty
└── payable() MorphTo

Product / Sku / Identity
└── orderItems() MorphMany
```

### 关联关系图

```
Order (1) ──── (N) OrderItem ──── (1) Product
                            ──── (1) Sku
                            ──── (1) Identity
```

---

## 阶段一：基础设施

### Step 1: 创建 OrderType 枚举

**文件：** `app/Enums/Mall/OrderType.php`

```php
<?php

namespace App\Enums\Mall;

use Filament\Support\Contracts\HasLabel;

enum OrderType: string implements HasLabel
{
    case Product = 'product';
    case Identity = 'identity';

    public function getLabel(): string
    {
        return match ($this) {
            self::Product => '商品订单',
            self::Identity => '身份订单',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Product => 'primary',
            self::Identity => 'success',
        };
    }
}
```

**验证：** 确保枚举可正常使用

---

### Step 2: order_items 表添加多态字段

**文件：** `database/migrations/2026_06_15_000001_add_payable_to_order_items_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // 多态关联字段
            $table->unsignedBigInteger('payable_id')
                ->nullable()
                ->after('sku_id')
                ->comment('关联对象ID');
            
            $table->string('payable_type')
                ->nullable()
                ->after('payable_id')
                ->comment('关联对象类型');
            
            // 索引
            $table->index(['payable_type', 'payable_id']);
        });

        // 为现有商品订单项设置关联
        DB::table('order_items')
            ->whereNotNull('product_id')
            ->whereNull('payable_id')
            ->update([
                'payable_id' => DB::raw('product_id'),
                'payable_type' => 'App\\Models\\Mall\\Product',
            ]);
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['payable_type', 'payable_id']);
            $table->dropColumn(['payable_id', 'payable_type']);
        });
    }
};
```

**验证：** 运行迁移，确认字段添加成功

---

## 阶段二：模型层改造

### Step 3: Order 模型改造

**文件：** `app/Models/Mall/Order.php`

**修改内容：**

```php
<?php

namespace App\Models\Mall;

use App\Enums\Mall\OrderType;
// ... 其他 use

class Order extends Model implements ShouldPayment
{
    // ... 现有 traits

    protected $casts = [
        // ... 现有 casts
        'order_type' => OrderType::class,  // 新增
    ];

    // ==================== 新增方法 ====================

    /**
     * 判断订单类型
     */
    public function isProductOrder(): bool
    {
        return $this->order_type === OrderType::Product;
    }

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
     * 获取订单关联的对象（通过第一个订单项）
     */
    public function getPayableAttribute()
    {
        return $this->items->first()?->payable;
    }

    /**
     * 获取订单的显示名称
     */
    public function getDisplayNameAttribute(): string
    {
        $firstItem = $this->items->first();
        
        if (!$firstItem) {
            return '空订单';
        }

        return $firstItem->product_name ?? $firstItem->payable?->name ?? '未知';
    }
}
```

**验证：** 
- 创建新订单，确认 order_type 字段正常
- `$order->payable` 通过第一个订单项返回正确对象

---

### Step 4: OrderItem 模型改造（核心）

**文件：** `app/Models/Mall/OrderItem.php`

**完整代码：**

```php
<?php

namespace App\Models\Mall;

use App\Models\Model;
use App\Models\Traits\BelongsToOrder;
use App\Models\User\Identity;
use App\Policies\Mall\OrderItemPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Unguarded]
#[UsePolicy(OrderItemPolicy::class)]
#[WithoutTimestamps]
class OrderItem extends Model
{
    use BelongsToOrder;

    protected $casts = [
        'price' => 'decimal:2',
    ];

    // ==================== 多态关联（核心）====================

    /**
     * 多态关联 - 可关联 Product / Sku / Identity 等
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    // ==================== 向后兼容关联 ====================

    /**
     * 关联商品（仅商品订单使用）
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)
            ->withTrashed();
    }

    /**
     * 关联规格（仅商品订单使用）
     */
    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class)
            ->withTrashed();
    }

    // ==================== 类型判断 ====================

    /**
     * 判断订单项类型
     */
    public function isProductItem(): bool
    {
        return $this->payable_type === Product::class;
    }

    public function isSkuItem(): bool
    {
        return $this->payable_type === Sku::class;
    }

    public function isIdentityItem(): bool
    {
        return $this->payable_type === Identity::class;
    }

    // ==================== 便捷访问器 ====================

    /**
     * 获取显示名称
     */
    public function getDisplayNameAttribute(): string
    {
        // 优先使用快照
        if ($this->product_name) {
            return $this->product_name;
        }

        // 从关联对象获取
        return $this->payable?->name ?? '未知';
    }

    /**
     * 获取封面图
     */
    public function getDisplayCoverAttribute(): ?string
    {
        // 快照封面
        if ($this->cover) {
            return $this->cover;
        }

        // 从关联对象获取
        return match ($this->payable_type) {
            Product::class => $this->payable?->cover_url,
            Sku::class => $this->payable?->cover_url,
            Identity::class => $this->payable?->cover_url,
            default => null,
        };
    }

    /**
     * 获取原价（用于显示划线价）
     */
    public function getOriginalPriceAttribute(): ?float
    {
        return match ($this->payable_type) {
            Product::class => $this->payable?->origin_price,
            Sku::class => $this->payable?->origin_price,
            Identity::class => null, // 身份没有原价概念
            default => null,
        };
    }

    // ==================== 计算属性 ====================

    /**
     * 小计金额
     */
    public function getSubTotalAttribute(): float
    {
        return (float) bcmul($this->qty, $this->price, 2);
    }

    // ==================== 关联 ====================

    /**
     * 关联退款明细
     */
    public function refundItem(): HasOne
    {
        return $this->hasOne(RefundItem::class);
    }

    /**
     * 关联物流
     */
    public function orderShipping(): BelongsTo
    {
        return $this->belongsTo(OrderShipping::class);
    }
}
```

**验证：** 
- 创建商品订单项，设置 `payable_type = Product::class, payable_id = product_id`
- 创建身份订单项，设置 `payable_type = Identity::class, payable_id = identity_id`
- 测试 `$item->payable` 返回正确对象
- 测试 `$item->display_name` 返回正确名称

---

### Step 5: Identity 模型关联

**文件：** `app/Models/User/Identity.php`

**添加关联：**

```php
<?php

namespace App\Models\User;

use App\Models\Mall\OrderItem;

class Identity extends Model
{
    // ... 现有代码

    /**
     * 关联订单项
     */
    public function orderItems(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(OrderItem::class, 'payable');
    }

    /**
     * 获取关联的订单（通过订单项）
     */
    public function orders()
    {
        return \App\Models\Mall\Order::whereIn(
            'id', 
            $this->orderItems()->pluck('order_id')
        )->get();
    }
}
```

**验证：** 
- `$identity->orderItems` 能正确查询关联订单项
- `$identity->orders` 能获取相关订单

---

## 阶段三：服务层拆分

### Step 6: 定义订单服务接口

**文件：** `app/Services/Mall/Contracts/OrderServiceInterface.php`

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
     * 订单支付成功回调
     */
    public function onPaid(Order $order): Order;

    /**
     * 完成订单
     */
    public function complete(Order $order, User $user): Order;
}
```

---

### Step 7: 抽取 ProductOrderService

**文件：** `app/Services/Mall/ProductOrderService.php`

**操作：** 从现有 `OrderService` 抽取商品订单相关逻辑

```php
<?php

namespace App\Services\Mall;

use App\Enums\Mall\OrderType;
use App\Models\Mall\Order;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;
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
                'amount' => $data['total_amount'],
                // ... 其他字段
            ]);

            foreach ($data['items'] as $item) {
                // 创建快照
                $snapshot = SnapshotService::create($item['product'], $item['sku']);
                
                // 创建订单项，设置多态关联
                $order->items()->create([
                    'payable_id' => $item['sku']->id,  // 或 product->id
                    'payable_type' => Sku::class,       // 或 Product::class
                    'product_name' => $item['product']->name,
                    'cover' => $item['sku']->cover ?? $item['product']->cover,
                    'price' => $item['sku']->price,
                    'qty' => $item['qty'],
                ]);
            }

            return $order;
        });
    }

    public function cancel(Order $order, User $user): Order
    {
        // 从 OrderService::cancelOrder 迁移
    }

    public function onPaid(Order $order): Order
    {
        // 从 OrderService::onPaid 迁移
        // 包含库存扣减逻辑
    }

    public function complete(Order $order, User $user): Order
    {
        // 从 OrderService::completeOrder 迁移
    }
}
```

**验证：** 
- 使用新服务创建商品订单，确认 `payable_type` 和 `payable_id` 正确
- 支付回调、取消、完成流程正常

---

### Step 8: 创建 IdentityOrderService

**文件：** `app/Services/Mall/IdentityOrderService.php`

```php
<?php

namespace App\Services\Mall;

use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\OrderType;
use App\Models\Mall\Order;
use App\Models\User\Identity;
use App\Models\User\User;
use App\Services\Mall\Contracts\OrderServiceInterface;
use Illuminate\Support\Facades\DB;

class IdentityOrderService implements OrderServiceInterface
{
    /**
     * 创建身份订单
     */
    public function create(User $user, Identity $identity, int $qty = 1): Order
    {
        return DB::transaction(function () use ($user, $identity, $qty) {
            $order = Order::create([
                'tenant_id' => tenant('id'),
                'order_type' => OrderType::Identity,
                'user_id' => $user->id,
                'amount' => $identity->price * $qty,
                'status' => OrderStatus::Pending,
            ]);

            // 创建订单项，设置多态关联到 Identity
            $order->items()->create([
                'payable_id' => $identity->id,
                'payable_type' => Identity::class,
                'product_name' => $identity->name,
                'cover' => $identity->cover,
                'price' => $identity->price,
                'qty' => $qty,
            ]);

            return $order;
        });
    }

    /**
     * 取消订单
     */
    public function cancel(Order $order, User $user): Order
    {
        $order->update(['status' => OrderStatus::Canceled]);
        return $order;
    }

    /**
     * 支付成功回调
     */
    public function onPaid(Order $order): Order
    {
        $order->update([
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);

        // 自动完成（身份订单无需物流）
        return $this->complete($order, $order->user);
    }

    /**
     * 完成订单 - 授予身份
     */
    public function complete(Order $order, User $user): Order
    {
        return DB::transaction(function () use ($order, $user) {
            // 从订单项获取身份
            $identity = Identity::findOrFail(
                $order->items->first()->payable_id
            );
            
            $qty = $order->items->sum('qty');
            $days = $identity->days * $qty;

            // 授予/续期身份
            $existingPivot = $user->identities()
                ->where('identity_id', $identity->id)
                ->first();

            if ($existingPivot) {
                // 续期
                $startFrom = $existingPivot->pivot->end_at && $existingPivot->pivot->end_at->isFuture()
                    ? $existingPivot->pivot->end_at
                    : now();
                
                $user->identities()->updateExistingPivot($identity->id, [
                    'start_at' => $startFrom,
                    'end_at' => $startFrom->addDays($days),
                ]);
            } else {
                // 新授予
                $user->identities()->attach($identity->id, [
                    'start_at' => now(),
                    'end_at' => now()->addDays($days),
                ]);
            }

            $order->update(['status' => OrderStatus::Completed]);
            return $order;
        });
    }
}
```

**验证：** 
- 创建身份订单，确认 `payable_type = Identity::class`
- 支付完成后，确认用户身份授予成功
- 续期逻辑正确

---

### Step 9: 统一支付回调服务

**文件：** `app/Services/Mall/PaymentCallbackService.php`

```php
<?php

namespace App\Services\Mall;

use App\Enums\Mall\OrderType;
use App\Models\Mall\Order;
use Illuminate\Support\Facades\Log;

class PaymentCallbackService
{
    public function __construct(
        protected ProductOrderService $productOrderService,
        protected IdentityOrderService $identityOrderService,
    ) {}

    /**
     * 处理支付成功回调
     */
    public function handlePaymentSuccess(Order $order): Order
    {
        $service = match ($order->order_type) {
            OrderType::Product => $this->productOrderService,
            OrderType::Identity => $this->identityOrderService,
            default => null,
        };

        if (!$service) {
            Log::error('未知的订单类型', [
                'order_id' => $order->id,
                'order_type' => $order->order_type,
            ]);
            throw new \InvalidArgumentException('未知的订单类型: ' . $order->order_type);
        }

        return $service->onPaid($order);
    }
}
```

**修改现有支付回调：**

```php
// 修改前
$order->status = OrderStatus::Paid;
$order->save();

// 修改后
app(PaymentCallbackService::class)->handlePaymentSuccess($order);
```

---

## 阶段四：Filament 后台适配

### Step 10: 订单列表显示类型

**文件：** `app/Filament/Tenant/Clusters/Mall/Resources/Orders/Tables/OrdersTable.php`

```php
use App\Enums\Mall\OrderType;

// 添加订单类型列
Tables\Columns\TextColumn::make('order_type')
    ->label('订单类型')
    ->badge()
    ->color(fn (OrderType $state) => $state->getColor())
    ->sortable(),

// 添加类型筛选
Tables\Filters\SelectFilter::make('order_type')
    ->label('订单类型')
    ->options(OrderType::class),
```

---

### Step 11: 订单详情按类型显示

**文件：** `app/Filament/Tenant/Clusters/Mall/Resources/Orders/Schemas/OrderInfolist.php`

```php
use App\Enums\Mall\OrderType;

// 订单项列表
Infolists\Components\RepeatableEntry::make('items')
    ->label('订单商品')
    ->schema([
        Infolists\Components\TextEntry::make('display_name')
            ->label('名称'),
        Infolists\Components\ImageEntry::make('display_cover')
            ->label('封面'),
        Infolists\Components\TextEntry::make('price')
            ->label('单价')
            ->prefix('¥'),
        Infolists\Components\TextEntry::make('qty')
            ->label('数量'),
        Infolists\Components\TextEntry::make('sub_total')
            ->label('小计')
            ->prefix('¥'),
    ])
    ->columns(5),
```

---

## 验证清单

### 阶段一验证
- [ ] OrderType 枚举可正常使用
- [ ] order_items 表 payable 字段正确

### 阶段二验证
- [ ] `$orderItem->payable` 返回正确对象
- [ ] `$orderItem->isProductItem()` / `isIdentityItem()` 正常
- [ ] `$identity->orderItems` 能查询关联订单项

### 阶段三验证
- [ ] ProductOrderService 创建商品订单正常
- [ ] IdentityOrderService 创建身份订单正常
- [ ] 支付回调按类型分发正常
- [ ] 身份订单完成后用户获得身份

### 阶段四验证
- [ ] 订单列表显示订单类型
- [ ] 订单项显示正确名称和封面

---

## 回滚方案

| 阶段 | 回滚操作 |
|------|---------|
| 阶段四 | 恢复 Filament 文件备份 |
| 阶段三 | 恢复 OrderService 原始代码 |
| 阶段二 | 恢复模型文件备份 |
| 阶段一 | 运行迁移 down 方法 |

---

## 时间估算

| 阶段 | 预计时间 | 复杂度 |
|------|---------|--------|
| 阶段一 | 0.5 天 | 低 |
| 阶段二 | 1 天 | 中 |
| 阶段三 | 1.5 天 | 高 |
| 阶段四 | 1 天 | 中 |
| **总计** | **4 天** | - |

---

## 注意事项

1. **多态关联在 OrderItem 级别** - 每个订单项可关联不同类型
2. **支持混合订单** - 一个订单可包含商品+身份（扩展性）
3. **向后兼容** - 保留 product_id、sku_id 字段
4. **快照字段** - product_name、cover 用于展示，不依赖关联
