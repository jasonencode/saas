<?php

namespace App\Filament\Actions\Mall;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;

class OrderCancelAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orderCancel';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('取消订单');
        $this->icon(Heroicon::OutlinedXCircle);
        $this->requiresConfirmation();
        $this->visible(fn (Order $order) => userCan('cancel', $order) && $order->status === OrderStatus::Pending);
        $this->action(function (Order $order) {
            try {
                app(OrderService::class)
                    ->cancel($order, Filament::auth()->user());

                $this->successNotificationTitle('订单取消成功');
                $this->success();
            } catch (Exception $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }
}