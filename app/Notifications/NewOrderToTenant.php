<?php

namespace App\Notifications;

use App\Channels\TenantChannel;
use App\Contracts\Authenticatable;
use App\Contracts\Notification\BaseNotification;
use App\Models\Mall\Order;
use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class NewOrderToTenant extends BaseNotification
{
    public function __construct(protected Order $order) {}

    public static function getGroupTitle(): string
    {
        return '新订单通知';
    }

    public function via(Authenticatable $user): array
    {
        return [TenantChannel::class];
    }

    /**
     * 通知里面，只处理发送内容，具体发送逻辑，交给渠道处理
     */
    public function toTenant(Tenant $tenant): Notification
    {
        return Notification::make()
            ->title('您有已付款订单请处理')
            ->body(sprintf('订单编号：%s, 付款金额：%s', $this->order->no, $this->order->getTotalAmount()))
            ->success()
            ->actions([
                Action::make('toViewPage')
                    ->label('查看订单')
                    ->url(fn () => route('filament.tenant.mall.resources.orders.view', ['tenant' => $tenant, 'record' => $this->order])),
            ]);
    }

    public static function getType(): string
    {
        return 'order';
    }

    public function getMessage(): string
    {
        return sprintf('订单编号：%s, 付款金额：%s', $this->order->no, $this->order->getTotalAmount());
    }
}
