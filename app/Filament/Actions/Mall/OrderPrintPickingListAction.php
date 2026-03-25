<?php

namespace App\Filament\Actions\Mall;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderService;
use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;

class OrderPrintPickingListAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'orderPrintPickingList';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('打印分拣单');
        $this->icon(Heroicon::OutlinedPrinter);
        $this->visible(fn (Order $order) => $order->status === OrderStatus::Paid);

        $this->action(function (Order $order) {
            try {
                resolve(OrderService::class)
                    ->preparing($order, Filament::auth()->user());

                $this->successNotificationTitle('打印分拣单成功');
                $this->success();
            } catch (Exception $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }
}
