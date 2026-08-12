<?php

namespace App\Notifications\Mall;

use App\Channels\TenantChannel;
use App\Contracts\Authenticatable;
use App\Contracts\Notification\BaseNotification;
use App\Models\Mall\Refund;
use App\Models\System\Tenant;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * 退款通过通知
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

    public function via(Authenticatable $user): array
    {
        return [TenantChannel::class];
    }

    public function toTenant(Tenant $tenant): Notification
    {
        return Notification::make()
            ->title('退款已通过审批')
            ->body(sprintf('退款单号：%s，退款金额：%s 元', $this->refund->no, $this->refund->total))
            ->success()
            ->actions([
                Action::make('toViewPage')
                    ->label('查看退款单')
                    ->url(fn () => route('filament.tenant.mall.resources.refunds.view', ['tenant' => $tenant, 'record' => $this->refund])),
            ]);
    }

    public function getUrl(Authenticatable $notifiable): string
    {
        return url('/user/refunds/'.$this->refund->no);
    }

    public function getMessage(): string
    {
        return sprintf('退款 %s 已通过审批，金额：¥%s', $this->refund->no, $this->refund->total);
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
