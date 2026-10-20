<?php

namespace App\Notifications\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\BaseNotification;
use App\Models\Mall\Order;
use Filament\Notifications\Notification;

/**
 * 订单支付成功通知
 */
class OrderPaidNotification extends BaseNotification
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

    public function toDatabase(Authenticatable $user): array
    {
        return Notification::make()
            ->title('订单支付成功')
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
        return sprintf('订单 %s 支付成功，金额：¥%s', $this->order->no, $this->order->getTotalAmount());
    }

    protected function getData(): array
    {
        return [
            'order_no' => $this->order->no,
            'amount' => $this->order->amount,
            'freight' => $this->order->freight,
            'total_amount' => $this->order->getTotalAmount(),
            'paid_at' => $this->order->paid_at?->toDateTimeString(),
        ];
    }
}
