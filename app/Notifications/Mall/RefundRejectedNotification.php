<?php

namespace App\Notifications\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\BaseNotification;
use App\Models\Mall\Refund;
use Filament\Notifications\Notification;

/**
 * 退款审核驳回通知（用户）
 */
class RefundRejectedNotification extends BaseNotification
{
    public function __construct(public Refund $refund, public string $reason = '')
    {
        //
    }

    public static function getGroupTitle(): string
    {
        return '退款通知';
    }

    public static function getType(): string
    {
        return 'refund_rejected';
    }

    public function getIcon(): string
    {
        return 'receipt-refund';
    }

    public function getColor(): string
    {
        return 'danger';
    }

    public function toDatabase(Authenticatable $user): array
    {
        return Notification::make()
            ->title('退款申请未通过')
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
        $msg = sprintf('退款 %s 未通过审核', $this->refund->no);

        if ($this->reason !== '') {
            $msg .= sprintf('，原因：%s', $this->reason);
        }

        return $msg;
    }

    protected function getData(): array
    {
        return [
            'refund_no' => $this->refund->no,
            'order_no' => $this->refund->order?->no,
            'total' => $this->refund->total,
            'reason' => $this->reason,
        ];
    }
}
