<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Services\Mall\OrderService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Throwable;

class OrderDelaySignAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orderDelaySign';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('延时签收');
        $this->icon(Heroicon::OutlinedClock);
        $this->color('warning');

        $this->visible(fn (Order $order): bool => userCan(self::getDefaultName(), $order)
            && $order->status === OrderStatus::Delivered);

        $this->requiresConfirmation();
        $this->modalHeading('延时签收');
        $this->modalDescription(fn (Order $order): string => sprintf(
            '当前自动签收时间：%s，确认后将再延长 7 天。',
            $order->auto_sign_at?->toDateTimeString() ?? '未设置',
        ));

        $this->action(function (Order $order, OrderService $service): void {
            try {
                $service->delaySign($order, Filament::auth()->user());

                $this->successNotificationTitle('已延时签收');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle('延时签收失败');
                $this->failureNotificationBody($e->getMessage());
                $this->failure();
            }
        });
    }
}
