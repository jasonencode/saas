<?php

namespace App\Notifications\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\BaseNotification;
use App\Models\Mall\Order;
/**
 * 订单创建通知（用户端）
 */
class OrderCreatedNotification extends BaseNotification
{
    public function __construct(public Order $order)
    {
        //
    }

    public static function getGroupTitle(): string
    {
        return '订单通知';
    }

    public static function getType(): string
    {
        return 'order_created';
    }

    public function getIcon(): string
    {
        return 'shopping-cart';
    }

    public function getColor(): string
    {
        return 'info';
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
        return sprintf('订单 %s 已创建，金额：¥%s', $this->order->no, $this->order->getTotalAmount());
    }

    protected function getData(): array
    {
        return [
            'order_no' => $this->order->no,
            'amount' => $this->order->amount,
            'freight' => $this->order->freight,
            'total_amount' => $this->order->getTotalAmount(),
        ];
    }
}
