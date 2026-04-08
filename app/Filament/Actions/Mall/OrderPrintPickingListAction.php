<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Services\OrderService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Throwable;

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
        $this->visible(fn (Order $order) => userCan('printPickingList', $order) && $order->status === OrderStatus::Paid);

        $this->action(function (Order $order) {
            try {
                service(OrderService::class)
                    ->preparing($order, Filament::auth()->user());

                $this->successNotificationTitle('打印分拣单成功');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }
}
