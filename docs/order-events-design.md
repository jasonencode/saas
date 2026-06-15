# 订单事件系统设计

> 创建时间：2026-06-15

---

## 事件设计

### 1. 订单事件类

```
app/Events/Mall/Order/
├── OrderCreated.php          // 订单创建
├── OrderPaid.php             // 订单支付成功
├── OrderCompleted.php        // 订单完成
├── OrderCanceled.php         // 订单取消
├── OrderRefunded.php         // 订单退款
└── IdentityOrderCompleted.php // 身份订单完成（专用）
```

### 2. 基础事件类

**文件：** `app/Events/Mall/Order/BaseOrderEvent.php`

```php
<?php

namespace App\Events\Mall\Order;

use App\Models\Mall\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class BaseOrderEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Order $order
    ) {}
}
```

### 3. 具体事件类

**文件：** `app/Events/Mall/Order/OrderPaid.php`

```php
<?php

namespace App\Events\Mall\Order;

class OrderPaid extends BaseOrderEvent
{
    // 继承基础事件，无需额外代码
}
```

**文件：** `app/Events/Mall/Order/OrderCompleted.php`

```php
<?php

namespace App\Events\Mall\Order;

class OrderCompleted extends BaseOrderEvent
{
    // 继承基础事件
}
```

**文件：** `app/Events/Mall/Order/IdentityOrderCompleted.php`

```php
<?php

namespace App\Events\Mall\Order;

use App\Models\Mall\Order;
use App\Models\User\Identity;

class IdentityOrderCompleted extends BaseOrderEvent
{
    public readonly Identity $identity;

    public function __construct(Order $order)
    {
        parent::__construct($order);
        
        // 预加载身份信息，方便监听器使用
        $this->identity = $order->items->first()?->payable;
    }
}
```

---

## 监听器设计

### 1. 监听器结构

```
app/Listeners/Mall/Order/
├── HandleProductOrderPaid.php      // 处理商品订单支付
├── HandleProductOrderCompleted.php // 处理商品订单完成
├── GrantIdentityOnOrderPaid.php    // 身份订单支付后授予身份
├── SendOrderNotification.php       // 发送订单通知
└── UpdateOrderStatistics.php       // 更新订单统计
```

### 2. 身份授予监听器

**文件：** `app/Listeners/Mall/Order/GrantIdentityOnOrderPaid.php`

```php
<?php

namespace App\Listeners\Mall\Order;

use App\Enums\Mall\OrderType;
use App\Events\Mall\Order\OrderPaid;
use App\Models\Mall\Order;
use App\Models\User\Identity;
use App\Models\User\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GrantIdentityOnOrderPaid implements ShouldQueue
{
    /**
     * 处理事件
     */
    public function handle(OrderPaid $event): void
    {
        $order = $event->order;

        // 只处理身份订单
        if ($order->order_type !== OrderType::Identity) {
            return;
        }

        try {
            $this->grantIdentity($order);
            
            // 触发身份订单完成事件
            IdentityOrderCompleted::dispatch($order);
            
        } catch (\Exception $e) {
            Log::error('身份授予失败', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * 授予身份
     */
    protected function grantIdentity(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $user = $order->user;
            $identity = Identity::findOrFail(
                $order->items->first()->payable_id
            );
            
            $qty = $order->items->sum('qty');
            $days = $identity->days * $qty;

            // 检查是否已有该身份
            $existingPivot = $user->identities()
                ->where('identity_id', $identity->id)
                ->first();

            if ($existingPivot) {
                // 续期
                $startFrom = $existingPivot->pivot->end_at 
                    && $existingPivot->pivot->end_at->isFuture()
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

            // 更新订单状态为已完成
            $order->update(['status' => \App\Enums\Mall\OrderStatus::Completed]);
        });
    }
}
```

### 3. 通知监听器（扩展示例）

**文件：** `app/Listeners/Mall/Order/SendOrderNotification.php`

```php
<?php

namespace App\Listeners\Mall\Order;

use App\Events\Mall\Order\OrderCompleted;
use App\Notifications\OrderCompletedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderNotification implements ShouldQueue
{
    public function handle(OrderCompleted $event): void
    {
        $order = $event->order;
        
        // 发送通知给用户
        $order->user->notify(
            new OrderCompletedNotification($order)
        );
    }
}
```

---

## 事件注册

**文件：** `app/Providers/EventServiceProvider.php`

```php
<?php

namespace App\Providers;

use App\Events\Mall\Order\OrderPaid;
use App\Events\Mall\Order\OrderCompleted;
use App\Events\Mall\Order\IdentityOrderCompleted;
use App\Listeners\Mall\Order\GrantIdentityOnOrderPaid;
use App\Listeners\Mall\Order\SendOrderNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // 订单支付成功
        OrderPaid::class => [
            GrantIdentityOnOrderPaid::class,     // 授予身份
            // HandleProductStock::class,         // 扣减库存（可选）
        ],

        // 订单完成
        OrderCompleted::class => [
            SendOrderNotification::class,         // 发送通知
            // UpdateOrderStatistics::class,      // 更新统计
        ],

        // 身份订单完成（可选，用于额外处理）
        IdentityOrderCompleted::class => [
            // SendIdentityNotification::class,   // 身份专属通知
            // LogIdentityChange::class,          // 记录变更日志
        ],
    ];
}
```

---

## 服务层改造

### IdentityOrderService 简化

```php
<?php

namespace App\Services\Mall;

use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\OrderType;
use App\Events\Mall\Order\OrderPaid;
use App\Models\Mall\Order;
use App\Models\User\Identity;
use App\Models\User\User;
use Illuminate\Support\Facades\DB;

class IdentityOrderService
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
     * 支付成功 - 只触发事件，不直接处理业务
     */
    public function onPaid(Order $order): Order
    {
        $order->update([
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);

        // 触发支付成功事件，监听器会处理身份授予
        OrderPaid::dispatch($order);

        return $order;
    }

    /**
     * 取消订单
     */
    public function cancel(Order $order, User $user): Order
    {
        $order->update(['status' => OrderStatus::Canceled]);
        return $order;
    }
}
```

---

## 完整流程图

```
用户支付
    ↓
PaymentCallbackService::handlePaymentSuccess($order)
    ↓
IdentityOrderService::onPaid($order)
    ├── 更新订单状态为 Paid
    └── 触发 OrderPaid 事件
            ↓
        Queue (异步处理)
            ↓
        GrantIdentityOnOrderPaid::handle($event)
            ├── 判断是否为身份订单
            ├── 授予/续期身份
            ├── 更新订单状态为 Completed
            └── 触发 IdentityOrderCompleted 事件
                    ↓
                其他监听器（通知、日志等）
```

---

## 优势

| 特性 | 说明 |
|------|------|
| **解耦** | 服务层只触发事件，不关心具体处理 |
| **异步** | 监听器可实现 ShouldQueue，异步处理 |
| **可扩展** | 新增监听器无需修改服务层 |
| **可测试** | 事件和监听器可独立测试 |
| **Laravel 标准** | 符合框架最佳实践 |

---

## 文件清单

```
新增文件：
├── app/Events/Mall/Order/
│   ├── BaseOrderEvent.php
│   ├── OrderPaid.php
│   ├── OrderCompleted.php
│   └── IdentityOrderCompleted.php
├── app/Listeners/Mall/Order/
│   ├── GrantIdentityOnOrderPaid.php
│   └── SendOrderNotification.php
└── app/Providers/EventServiceProvider.php (修改)
```
