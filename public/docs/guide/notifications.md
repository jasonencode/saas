# 通知系统使用文档

## 📋 概述

本系统支持多通道通知：数据库（站内通知）、短信、微信小程序、微信公众号、钉钉、极光推送（JPush）、租户通道。

通知类继承 `App\Contracts\Notification\BaseNotification`，由 Laravel Notification 机制统一调度：

- `database` 通道自动落库为站内通知（`Content\Notification` 模型），供后台「通知管理」与用户中心「通知」接口使用
- 其他通道在 `via()` 中按需声明，由 `app/Channels/` 下的自定义 Channel 实现

---

## 🏗️ 架构设计

### BaseNotification 基类

```php
// app/Contracts/Notification/BaseNotification.php
abstract class BaseNotification extends Notification
{
    /**
     * 获取通知分组标题
     */
    public static function getGroupTitle(): string
    {
        return '系统通知';
    }

    /**
     * 获取通知类型标识
     */
    public static function getType(): string
    {
        return static::class;
    }

    /**
     * 获取通知图标（Heroicon 名称）
     */
    public function getIcon(): string
    {
        return 'bell';
    }

    /**
     * 获取通知颜色
     */
    public function getColor(): string
    {
        return 'info';
    }

    /**
     * 获取通知跳转地址
     */
    abstract public function getUrl(Authenticatable $notifiable): string;

    /**
     * 获取通知消息（摘要）
     */
    abstract public function getMessage(): string;
}
```

### 通道列表

| 通道 | 类 | 说明 |
|------|-----|------|
| database | 内置 | 站内通知，落库到通知表 |
| sms | `App\Channels\SmsChannel` | 短信通知（阿里云短信） |
| wechat_mini | `App\Channels\WechatMiniChannel` | 微信小程序订阅消息 |
| wechat_official | `App\Channels\WechatOfficialChannel` | 微信公众号模板消息 |
| ding_talk | `App\Channels\DingTalkChannel` | 钉钉机器人通知 |
| jpush | `App\Channels\JPushChannel` | 极光推送（App 推送） |
| tenant | `App\Channels\TenantChannel` | 租户维度通知（接收者为 `Tenant` 模型） |

> 自定义通道需在用户 / 租户模型的 `routeNotificationFor{ChannelName}` 方法中配置接收路由（手机号、小程序 openid 等）。

---

## 🚀 使用方法

### 1. 创建通知类

```php
// app/Notifications/Mall/OrderPaidNotification.php
<?php

namespace App\Notifications\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\BaseNotification;
use App\Models\Mall\Order;

class OrderPaidNotification extends BaseNotification
{
    public function __construct(public Order $order)
    {
    }

    public static function getGroupTitle(): string
    {
        return '订单通知';
    }

    public static function getType(): string
    {
        return 'order_paid';
    }

    public function getIcon(): string
    {
        return 'check-circle';
    }

    public function getColor(): string
    {
        return 'success';
    }

    public function via(Authenticatable $user): array
    {
        return ['database'];
    }

    public function getUrl(Authenticatable $notifiable): string
    {
        return url('/user/orders/'.$this->order->no);
    }

    public function getMessage(): string
    {
        return "订单 {$this->order->no} 已支付成功";
    }
}
```

### 2. 发送通知

```php
use App\Notifications\Mall\OrderPaidNotification;

$user->notify(new OrderPaidNotification($order));

// 延迟发送
$user->notifyLater(now()->addMinutes(5), new OrderPaidNotification($order));

// 立即发送（不入队列）
$user->notifyNow(new OrderPaidNotification($order));
```

### 3. 多通道发送

```php
public function via(Authenticatable $user): array
{
    return ['database', 'wechat_mini'];
}

/**
 * 微信模板消息数据结构
 */
public function toWechat_mini(Authenticatable $user): array
{
    return [
        'template_id' => 'xxx',
        'data' => [
            'order_no' => $this->order->no,
            'amount' => $this->order->total_amount,
        ],
    ];
}
```

---

## 📦 现有通知类

### Mall 模块（`app/Notifications/Mall/`）

| 通知类 | 类型标识 | 说明 |
|--------|----------|------|
| `OrderCreatedNotification` | `order_created` | 订单创建通知 |
| `OrderPaidNotification` | `order_paid` | 订单支付成功通知 |
| `OrderDeliveredNotification` | `order_delivered` | 订单发货通知 |
| `OrderSignedNotification` | `order_signed` | 订单签收通知 |
| `RefundApprovedNotification` | `refund_approved` | 退款审核通过通知 |
| `RefundRejectedNotification` | `refund_rejected` | 退款审核驳回通知 |
| `RefundCompletedNotification` | `refund_completed` | 退款完成通知 |
| `StoreApplyReviewedNotification` | `store_apply_reviewed` | 店铺申请审核结果通知 |

### Finance 模块（`app/Notifications/Finance/`）

| 通知类 | 类型标识 | 说明 |
|--------|----------|------|
| `InvoiceApplicationSubmittedNotification` | `invoice_application_submitted` | 发票申请提交通知 |

### 事件触发

多数业务通知由事件监听器在关键节点自动发送（`app/Listeners/` 按模块分组）：

- 订单创建 / 支付 / 发货 / 签收 → `Order*Notification`
- 退款状态流转 → `Refund*Notification`
- 订单支付成功 → 自动授予身份（`GrantIdentityOnOrderPaid`）

---

## 📊 用户通知 API

通知落库后通过用户中心 API 暴露（需登录，详见 [用户中心 API](../api/user-center-api)）：

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/user/notifications` | 通知列表（分页，可按 `group_title` / `type` 过滤） |
| GET | `/user/notifications/groups` | 通知分组统计（各分组总数与未读数） |
| GET | `/user/notifications/count` | 未读通知数量 |
| PUT | `/user/notifications/{notification}/read` | 标记已读 |
| PUT | `/user/notifications/read-all` | 全部标记已读 |
| DELETE | `/user/notifications/{notification}` | 删除通知 |

---

## 🔧 后台管理

通知在 `Content` 集群的 `NotificationResource`（`Content/Resources/Notifications/`）中管理：

- 查看所有用户通知
- 手动发送通知
- 删除通知记录

---

## 💡 最佳实践

1. **抽象基类** - 所有通知继承 `BaseNotification`，统一分组 / 图标 / 跳转
2. **类型标识** - `getType()` 使用蛇形命名（如 `order_paid`），便于前端图标与过滤
3. **延迟发送** - 非实时通知使用 `notifyLater()` 降低请求耗时
4. **幂等设计** - 事件监听器中防止重复发送（如订单状态回滚场景）
5. **降级策略** - 第三方通道（短信 / 微信）失败时站内通知仍保留
6. **测试覆盖** - 使用 `Notification::fake()` 断言通知发送：

```php
use Illuminate\Support\Facades\Notification;
use App\Notifications\Mall\OrderPaidNotification;

Notification::fake();

// ... 触发订单支付

Notification::assertSentOnDemand($user, OrderPaidNotification::class);
```
