<?php

namespace App\Notifications\Finance;

use App\Contracts\Authenticatable;
use App\Contracts\Notification\BaseNotification;
use App\Models\Finance\PaymentRefund;
use Filament\Notifications\Notification;

/**
 * 支付退款已发起通知（用户）
 */
class PaymentRefundInitiatedNotification extends BaseNotification
{
    public function __construct(public PaymentRefund $refund)
    {
        //
    }

    public static function getGroupTitle(): string
    {
        return '退款通知';
    }

    public static function getType(): string
    {
        return 'payment_refund_initiated';
    }

    public function getIcon(): string
    {
        return 'receipt-refund';
    }

    public function getColor(): string
    {
        return 'warning';
    }

    public function toDatabase(Authenticatable $user): array
    {
        return Notification::make()
            ->title('退款已发起')
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
        return url('/user/orders');
    }

    public function getMessage(): string
    {
        return sprintf(
            '支付单 %s 的退款 %s（¥%s）已发起，将按原支付方式退回。',
            $this->refund->paymentOrder?->no ?? '-',
            $this->refund->no,
            $this->refund->amount,
        );
    }

    protected function getData(): array
    {
        return [
            'refund_no' => $this->refund->no,
            'payment_no' => $this->refund->paymentOrder?->no,
            'amount' => $this->refund->amount,
            'reason' => $this->refund->reason,
        ];
    }
}
