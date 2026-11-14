<?php

namespace App\Notifications\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\BaseNotification;
use App\Models\Mall\Refund;
use Filament\Notifications\Notification;

/**
 * 退款失败通知（用户）
 */
class RefundFailedUserNotification extends BaseNotification
{
    public function __construct(public Refund $refund)
    {
        //
    }

    public static function getGroupTitle(): string
    {
        return '退款通知';
    }

    public static function getType(): string
    {
        return 'refund_failed';
    }

    public function getIcon(): string
    {
        return 'exclamation-triangle';
    }

    public function getColor(): string
    {
        return 'danger';
    }

    public function toDatabase(Authenticatable $user): array
    {
        return Notification::make()
            ->title('退款处理失败')
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
        return url('/user/refunds/'.$this->refund->no);
    }

    public function getMessage(): string
    {
        return sprintf('退款 %s 处理失败，我们会尽快重新处理，请留意退款状态', $this->refund->no);
    }

    protected function getData(): array
    {
        return [
            'refund_no' => $this->refund->no,
            'order_no' => $this->refund->order->no,
            'total' => $this->refund->total,
        ];
    }
}
