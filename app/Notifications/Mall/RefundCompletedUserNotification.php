<?php

namespace App\Notifications\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\BaseNotification;
use App\Models\Mall\Refund;
use Filament\Notifications\Notification;

/**
 * 退款完成通知（用户）
 */
class RefundCompletedUserNotification extends BaseNotification
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
        return 'refund_completed';
    }

    public function getIcon(): string
    {
        return 'banknotes';
    }

    public function getColor(): string
    {
        return 'success';
    }

    public function toDatabase(Authenticatable $user): array
    {
        return Notification::make()
            ->title('退款已完成')
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
        return sprintf('退款 %s 已完成，金额：¥%s 已原路退回', $this->refund->no, $this->refund->total);
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
