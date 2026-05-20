<?php

namespace App\Notifications\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\BaseNotification;
use App\Models\Mall\Order;

/**
 * 订单发货通知（含物流信息）
 */
class OrderDeliveredNotification extends BaseNotification
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
        return 'order_delivered';
    }

    public function getIcon(): string
    {
        return 'truck';
    }

    public function getColor(): string
    {
        return 'warning';
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
        $shippingNos = $this->order->shippings->pluck('no')->implode(', ');

        return sprintf('订单 %s 已发货，运单号：%s', $this->order->no, $shippingNos ?: '待分配');
    }

    protected function getData(): array
    {
        return [
            'order_no' => $this->order->no,
            'shippings' => $this->order->shippings->map(fn ($shipping) => [
                'no' => $shipping->no,
                'express_name' => $shipping->express?->name,
            ])->toArray(),
        ];
    }
}
