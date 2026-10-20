<?php

namespace Tests\Feature\Mall;

use App\Events\Mall\OrderCanceled;
use App\Events\Mall\OrderCompleted;
use App\Events\Mall\OrderDelivered;
use App\Events\Mall\OrderPaid;
use App\Events\Mall\OrderPartiallyShipped;
use App\Events\Mall\OrderPreparing;
use App\Events\Mall\OrderSigned;
use App\Listeners\Mall\OrderCanceledListener;
use App\Listeners\Mall\OrderCompletedListener;
use App\Listeners\Mall\OrderDeliveredListener;
use App\Listeners\Mall\OrderPaidListener;
use App\Listeners\Mall\OrderPartiallyShippedListener;
use App\Listeners\Mall\OrderPreparingListener;
use App\Listeners\Mall\OrderSignedListener;
use App\Models\Mall\Order;
use App\Notifications\Mall\OrderCanceledNotification;
use App\Notifications\Mall\OrderCompletedNotification;
use App\Notifications\Mall\OrderDeliveredNotification;
use App\Notifications\Mall\OrderPaidNotification;
use App\Notifications\Mall\OrderPartiallyShippedNotification;
use App\Notifications\Mall\OrderPreparingNotification;
use App\Notifications\Mall\OrderSignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 订单事件、监听器与对应通知
     *
     * @return array<string, array{class-string, class-string, class-string, string}>
     */
    public static function orderEventProvider(): array
    {
        return [
            'paid' => [OrderPaid::class, OrderPaidListener::class, OrderPaidNotification::class, '订单支付成功'],
            'delivered' => [OrderDelivered::class, OrderDeliveredListener::class, OrderDeliveredNotification::class, '订单已发货'],
            'signed' => [OrderSigned::class, OrderSignedListener::class, OrderSignedNotification::class, '订单已签收'],
            'canceled' => [OrderCanceled::class, OrderCanceledListener::class, OrderCanceledNotification::class, '订单已取消'],
            'completed' => [OrderCompleted::class, OrderCompletedListener::class, OrderCompletedNotification::class, '订单已完成'],
            'preparing' => [OrderPreparing::class, OrderPreparingListener::class, OrderPreparingNotification::class, '订单备货中'],
            'partially_shipped' => [OrderPartiallyShipped::class, OrderPartiallyShippedListener::class, OrderPartiallyShippedNotification::class, '订单部分发货'],
        ];
    }

    /**
     * 事件均已被监听器订阅
     */
    public function test_order_events_have_listeners(): void
    {
        Event::fake();

        foreach (self::orderEventProvider() as [$eventClass, $listenerClass]) {
            Event::assertListening($eventClass, $listenerClass);
        }
    }

    /**
     * 监听器向订单用户发送对应通知
     *
     * @param  class-string  $eventClass
     * @param  class-string  $listenerClass
     * @param  class-string  $notificationClass
     */
    #[DataProvider('orderEventProvider')]
    public function test_order_listener_notifies_user(
        string $eventClass,
        string $listenerClass,
        string $notificationClass,
        string $title,
    ): void {
        Notification::fake();

        $order = Order::factory()->create();

        (new $listenerClass)->handle(new $eventClass($order));

        Notification::assertSentTo($order->user, $notificationClass);

        $sent = Notification::sent($order->user, $notificationClass)->first();

        $this->assertNotNull($sent);
        $this->assertSame($title, $sent->toDatabase($order->user)['title']);
    }

    /**
     * 通知可写入数据库且类型为类名（供分组与前端识别）
     *
     * @param  class-string  $eventClass
     * @param  class-string  $listenerClass
     * @param  class-string  $notificationClass
     */
    #[DataProvider('orderEventProvider')]
    public function test_order_notification_database_payload(
        string $eventClass,
        string $listenerClass,
        string $notificationClass,
        string $title,
    ): void {
        $order = Order::factory()->create();
        $notification = new $notificationClass($order);

        $this->assertSame($notificationClass, $notification->databaseType($order->user));

        $data = $notification->toDatabase($order->user);

        $this->assertSame($title, $data['title']);
        $this->assertNotEmpty($data['body']);
        $this->assertSame('订单通知', resolve($notification->databaseType($order->user))->getGroupTitle());
    }
}
