<?php

namespace App\Notifications\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\BaseNotification;
use App\Models\Mall\Refund;
/**
 * 退款驳回通知
 */
class RefundRejectedNotification extends BaseNotification
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
        return sprintf('退款 %s 未通过审批', $this->refund->no);
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
