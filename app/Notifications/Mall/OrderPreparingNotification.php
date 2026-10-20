<?php

namespace App\Notifications\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\BaseNotification;
use App\Models\Mall\Order;
use Filament\Notifications\Notification;

/**
 * 订单备货通知
 */
class OrderPreparingNotification extends BaseNotification
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
        return 'order_preparing';
    }

    public function getIcon(): string
    {
        return 'archive-box';
    }

    public function getColor(): string
    {
        return 'info';
    }

    public function toDatabase(Authenticatable $user): array
    {
        return Notification::make()
            ->title('订单备货中')
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
        return sprintf('订单 %s 正在备货，请耐心等待', $this->order->no);
    }

    protected function getData(): array
    {
        return [
            'order_no' => $this->order->no,
            'status' => $this->order->status->value,
        ];
    }
}
