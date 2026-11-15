<?php

namespace App\Notifications\Mall;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\BaseNotification;
use App\Enums\Mall\RefundType;
use App\Models\Mall\Refund;
use Filament\Notifications\Notification;

/**
 * 退款审核通过通知（用户）
 */
class RefundApprovedNotification extends BaseNotification
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
        return 'refund_approved';
    }

    public function getIcon(): string
    {
        return 'receipt-refund';
    }

    public function getColor(): string
    {
        return 'success';
    }

    public function toDatabase(Authenticatable $user): array
    {
        return Notification::make()
            ->title('退款申请已通过')
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
        return $this->refund->type === RefundType::ReturnRefund
            ? sprintf('退款 %s 已通过审核，请按页面提示寄回商品，金额：¥%s', $this->refund->no, $this->refund->total)
            : sprintf('退款 %s 已通过审核，金额：¥%s 正在处理中', $this->refund->no, $this->refund->total);
    }

    protected function getData(): array
    {
        return [
            'refund_no' => $this->refund->no,
            'order_no' => $this->refund->order?->no,
            'total' => $this->refund->total,
        ];
    }
}
