<?php

namespace App\Notifications\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\BaseNotification;
use App\Models\Mall\Order;
/**
 * 订单签收通知
 */
class OrderSignedNotification extends BaseNotification
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
        return 'order_signed';
    }

    public function getIcon(): string
    {
        return 'clipboard-check';
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
        return sprintf('订单 %s 已签收', $this->order->no);
    }

    protected function getData(): array
    {
        return [
            'order_no' => $this->order->no,
            'signed_at' => $this->order->signed_at?->toDateTimeString(),
        ];
    }
}
