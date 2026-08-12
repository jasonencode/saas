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
 * 退款驳回通知
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

    public function via(Authenticatable $user): array
    {
        return [TenantChannel::class];
    }

    public function toTenant(Tenant $tenant): Notification
    {
        return Notification::make()
            ->title('退款已被驳回')
            ->body(sprintf('退款单号：%s，驳回原因：%s', $this->refund->no, $this->reason ?: '无'))
            ->danger()
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
        $msg = sprintf('退款 %s 未通过审批', $this->refund->no);
        if ($this->reason) {
            $msg .= sprintf('，原因：%s', $this->reason);
        }

        return $msg;
    }

    protected function getData(): array
    {
        return [
            'refund_no' => $this->refund->no,
            'order_no' => $this->refund->order->no,
            'total' => $this->refund->total,
            'reason' => $this->reason,
        ];
    }
}
