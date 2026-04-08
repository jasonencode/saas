<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Services\OrderService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Throwable;

class OrderSignAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orderSign';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('签收');
        $this->icon(Heroicon::OutlinedCheckCircle);
        $this->visible(fn (Order $order) => userCan('sign', $order) && $order->status === OrderStatus::Delivered);
        $this->requiresConfirmation();
        $this->action(function (Order $order) {
            try {
                service(OrderService::class)
                    ->sign($order, Filament::auth()->user());
                $this->successNotificationTitle('签收成功');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }
}