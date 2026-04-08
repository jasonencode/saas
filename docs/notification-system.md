# 通知系统使用文档

## 1. 系统架构

### 1.1 核心组件

- **BaseNotification** - 基础通知抽象类
- **Notification** - 具体通知类
- **Channel** - 通知通道
- **Listener** - 事件监听器

### 1.2 目录结构

```
app/
├── Contracts/Notification/
│   ├── BaseNotification.php       # 基础通知抽象类
│   ├── WechatMiniMessage.php      # 微信小程序消息接口
│   └── ...
├── Notifications/                 # 具体通知类
│   ├── Finance/                   # 财务相关通知
│   ├── Mall/                      # 商城相关通知
│   └── ...
├── Channels/                      # 通知通道
│   ├── WechatMiniChannel.php      # 微信小程序通道
│   ├── SmsChannel.php             # 短信通道
│   └── ...
└── Listeners/                     # 事件监听器
    ├── Finance/                   # 财务相关监听器
    ├── Mall/                      # 商城相关监听器
    └── ...
```

## 2. 基础通知类

### 2.1 BaseNotification 特性

- **队列支持**：自动加入队列处理
- **统一接口**：标准化的通知方法
- **灵活配置**：可自定义图标、颜色、链接等
- **错误处理**：内置重试机制

### 2.2 核心方法

| 方法 | 描述 | 必须实现 |
|------|------|----------|
| `getGroupTitle()` | 获取通知分组标题 | ✅ |
| `getType()` | 获取通知类型标识 | ✅ |
| `getMessage()` | 获取通知消息内容 | ✅ |
| `getIcon()` | 获取通知图标 | ❌ (默认: bell) |
| `getColor()` | 获取通知颜色 | ❌ (默认: primary) |
| `getData()` | 获取通知附加数据 | ❌ (默认: []) |
| `getUrl()` | 获取通知链接 | ❌ (默认: null) |
| `via()` | 指定通知通道 | ❌ (默认: ['database']) |

## 3. 创建新通知

### 3.1 步骤

1. **创建通知类**：继承 `BaseNotification`
2. **实现必要方法**：`getGroupTitle()`, `getType()`, `getMessage()`
3. **配置通知通道**：重写 `via()` 方法
4. **添加附加功能**：如邮件通知、自定义数据等

### 3.2 示例

```php
<?php

namespace App\Notifications\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\BaseNotification;
use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * 订单支付成功通知
 */
class OrderPaidNotification extends BaseNotification
{
    public function __construct(public Order $order)
    {
        //
    }

    /**
     * 获取通知分组标题
     */
    public static function getGroupTitle(): string
    {
        return '订单通知';
    }

    /**
     * 获取通知类型
     */
    public static function getType(): string
    {
        return 'order_paid';
    }

    /**
     * 获取通知图标
     */
    public function getIcon(): string
    {
        return 'shopping-cart';
    }

    /**
     * 获取通知颜色
     */
    public function getColor(): string
    {
        return 'success';
    }

    /**
     * 发送通道
     */
    public function via(Authenticatable $user): array
    {
        return ['mail', 'database'];
    }

    /**
     * 邮件通知
     */
    public function toMail(Authenticatable $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('订单支付成功')
            ->greeting('您好！')
            ->line('您的订单已支付成功，我们将尽快为您发货。')
            ->line('订单编号：' . $this->order->order_no)
            ->line('支付金额：¥' . $this->order->amount)
            ->action('查看订单详情', $this->getUrl($notifiable))
            ->line('感谢您的购买！');
    }

    /**
     * 获取通知消息
     */
    public function getMessage(): string
    {
        return '您的订单已支付成功，我们将尽快为您发货。';
    }

    /**
     * 获取通知数据
     */
    protected function getData(): array
    {
        return [
            'order_id' => $this->order->id,
            'order_no' => $this->order->order_no,
            'amount' => $this->order->amount,
        ];
    }

    /**
     * 获取通知链接
     */
    public function getUrl(Authenticatable $notifiable): string
    {
        return url('/user/orders/' . $this->order->id);
    }
}
```

## 4. 发送通知

### 4.1 直接发送

```php
use App\Notifications\Mall\OrderPaidNotification;

// 发送给单个用户
$user->notify(new OrderPaidNotification($order));

// 发送给多个用户
Notification::send($users, new OrderPaidNotification($order));
```

### 4.2 事件触发

1. **创建事件**：

```php
<?php

namespace App\Events\Mall;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPaid
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order)
    {
        //
    }
}
```

2. **创建监听器**：

```php
<?php

namespace App\Listeners\Mall;

use App\Events\Mall\OrderPaid;
use App\Notifications\Mall\OrderPaidNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OrderPaidListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderPaid $event): void
    {
        $order = $event->order;
        $user = $order->user;

        // 发送通知给用户
        $user->notify(new OrderPaidNotification($order));
    }
}
```

3. **注册事件**：

在 `EventServiceProvider.php` 中注册：

```php
protected $listen = [
    OrderPaid::class => [
        OrderPaidListener::class,
    ],
];
```

4. **触发事件**：

```php
use App\Events\Mall\OrderPaid;

// 触发事件
OrderPaid::dispatch($order);
```

## 5. 通知通道

### 5.1 内置通道

- **database** - 数据库通知（默认）
- **mail** - 邮件通知
- **sms** - 短信通知
- **wechat-mini** - 微信小程序通知
- **wechat-official** - 微信公众号通知
- **dingtalk** - 钉钉通知
- **jpush** - 极光推送

### 5.2 自定义通道

1. **创建通道类**：

```php
<?php

namespace App\Channels;

use App\Contracts\Authenticatable;
use Illuminate\Notifications\Notification;

class CustomChannel
{
    public function send(Authenticatable $user, Notification $notification): void
    {
        // 实现发送逻辑
    }
}
```

2. **在通知中使用**：

```php
public function via(Authenticatable $user): array
{
    return ['custom', 'database'];
}

public function toCustom(Authenticatable $notifiable)
{
    // 返回自定义消息结构
    return [
        'message' => $this->getMessage(),
        'data' => $this->getData(),
    ];
}
```

## 6. 微信小程序通知

### 6.1 配置

在 `.env` 文件中配置：

```env
WECHAT_MINI_APP_APPID=your_app_id
WECHAT_MINI_APP_SECRET=your_secret
```

在 `config/easywechat.php` 中启用小程序配置：

```php
'mini_app' => [
    'default' => [
        'app_id' => env('WECHAT_MINI_APP_APPID', ''),
        'secret' => env('WECHAT_MINI_APP_SECRET', ''),
    ],
],
```

### 6.2 创建消息类

```php
<?php

namespace App\Notifications\Mall;

use App\Contracts\Notification\WechatMiniMessage;

class OrderPaidWechatMiniMessage implements WechatMiniMessage
{
    protected $order;
    protected $user;

    public function __construct($order, $user)
    {
        $this->order = $order;
        $this->user = $user;
    }

    public function getTemplateId(): string
    {
        return 'TEMPLATE_ID_HERE';
    }

    public function getData(): array
    {
        return [
            'thing1' => ['value' => $this->order->order_no],
            'amount2' => ['value' => $this->order->amount],
            'time3' => ['value' => $this->order->paid_at->format('Y-m-d H:i')],
        ];
    }

    public function getPage(): ?string
    {
        return "/pages/order/detail?id={$this->order->id}";
    }

    public function getToUser(): string
    {
        return $this->user->wechat_openid;
    }
}
```

### 6.3 在通知中使用

```php
public function via(Authenticatable $user): array
{
    return ['wechat-mini', 'database'];
}

public function toWechatMini(Authenticatable $notifiable)
{
    return new OrderPaidWechatMiniMessage($this->order, $notifiable);
}
```

## 7. 通知管理

### 7.1 查看通知

- **用户端**：`/user/notifications`
- **后台**：Filament 管理面板中的通知管理

### 7.2 通知状态

- **未读**：用户未查看的通知
- **已读**：用户已查看的通知
- **已删除**：用户已删除的通知

### 7.3 通知分组

通知会根据 `getGroupTitle()` 方法返回的值进行分组显示。

## 8. 最佳实践

### 8.1 命名规范

- **通知类**：`{Action}{Target}Notification`（如 `OrderPaidNotification`）
- **事件类**：`{Action}{Target}`（如 `OrderPaid`）
- **监听器类**：`{Action}{Target}Listener`（如 `OrderPaidListener`）

### 8.2 性能优化

- **使用队列**：所有通知都应使用队列处理
- **批量发送**：对于批量通知，使用 `Notification::send()`
- **合理设置重试**：根据通知重要性设置合适的重试次数

### 8.3 安全性

- **数据验证**：确保通知数据安全，避免 XSS 攻击
- **权限控制**：确保用户只能查看自己的通知
- **敏感信息**：避免在通知中包含敏感信息

## 9. 故障排除

### 9.1 通知不发送

1. 检查队列是否运行：`php artisan queue:listen`
2. 检查事件监听器是否正确注册
3. 检查通道配置是否正确
4. 查看日志：`storage/logs/laravel.log`

### 9.2 微信小程序通知失败

1. 检查小程序配置是否正确
2. 检查用户是否授权订阅消息
3. 检查模板 ID 是否正确
4. 检查消息数据格式是否符合模板要求

## 10. 示例通知类型

| 通知类型 | 触发场景 | 通道 |
|----------|----------|------|
| OrderPaidNotification | 订单支付成功 | 邮件、数据库 |
| OrderShippedNotification | 订单发货 | 邮件、数据库、微信小程序 |
| InvoiceApplicationSubmittedNotification | 发票申请提交 | 邮件、数据库 |
| UserRealnameApprovedNotification | 实名认证通过 | 邮件、数据库、短信 |
| TenantExpiredNotification | 租户即将到期 | 邮件、数据库 |

## 11. 总结

本通知系统提供了一个灵活、可扩展的框架，支持多种通知通道和场景。通过遵循上述规范和最佳实践，您可以轻松实现各种通知功能，为用户提供及时、准确的信息反馈。

---

**版本**：1.0.0
**更新时间**：2026-04-08
