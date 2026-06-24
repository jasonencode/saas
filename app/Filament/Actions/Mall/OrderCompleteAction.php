<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Services\Mall\OrderService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Throwable;

class OrderCompleteAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('完成订单');
        $this->icon(Heroicon::OutlinedCheckBadge);
        $this->color('success');
        $this->visible(fn (Order $order): bool => userCan(self::getDefaultName(), $order) && $order->status === OrderStatus::Signed);
        $this->requiresConfirmation();
        $this->modalHeading('确认完成订单？');
        $this->modalDescription('订单完成后将进入结算阶段，不可再发起售后申请。');

        $this->action(function (Order $order, OrderService $service): void {
            try {
                $service->complete($order, Filament::auth()->user());

                $this->successNotificationTitle('订单已标记为完成');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'orderComplete';
    }
}
