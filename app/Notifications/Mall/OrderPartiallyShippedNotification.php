<?php

namespace App\Notifications\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\BaseNotification;
use App\Models\Mall\Order;
use Filament\Notifications\Notification;

/**
 * 订单部分发货通知
 */
class OrderPartiallyShippedNotification extends BaseNotification
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
        return 'order_partially_shipped';
    }

    public function getIcon(): string
    {
        return 'truck';
    }

    public function getColor(): string
    {
        return 'warning';
    }

    public function toDatabase(Authenticatable $user): array
    {
        return Notification::make()
            ->title('订单部分发货')
            ->body($this->getMessage())
            ->icon($this->getIcon())
            ->color($this->getColor())
            ->toDatabase()
            ->toArray();
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

        return sprintf('订单 %s 已部分发货，运单号：%s', $this->order->no, $shippingNos ?: '待分配');
    }

    protected function getData(): array
    {
        return [
            'order_no' => $this->order->no,
            'status' => $this->order->status->value,
            'shippings' => $this->order->shippings->map(fn ($shipping) => [
                'no' => $shipping->no,
                'express_name' => $shipping->express?->name,
            ])->toArray(),
        ];
    }
}
