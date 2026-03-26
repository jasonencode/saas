<?php

namespace App\Filament\Actions\Mall;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;

class OrderCompleteAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orderComplete';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('完成订单');
        $this->icon(Heroicon::OutlinedCheckBadge);
        $this->color('success');
        $this->visible(fn (Order $order) => $order->status === OrderStatus::Signed);
        $this->requiresConfirmation();
        $this->modalHeading('确认完成订单？');
        $this->modalDescription('订单完成后将进入结算阶段，不可再发起售后申请。');

        $this->action(function (Order $order, OrderService $service) {
            $service->complete($order, Filament::auth()->user());

            $this->successNotificationTitle('订单已标记为完成');
            $this->success();
        });
    }
}
