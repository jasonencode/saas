<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Services\Mall\OrderService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;
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

        $this->visible(fn (Order $order): bool => userCan(self::getDefaultName(), $order) && in_array($order->status, [OrderStatus::Paid, OrderStatus::Preparing], true));

        $this->action(function (Order $order, Component $livewire): void {
            try {
                if ($order->status === OrderStatus::Paid) {
                    service(OrderService::class)
                        ->preparing($order, Filament::auth()->user());
                }

                $order->loadMissing([
                    'items.orderable.product',
                    'items.refundItems.refund',
                    'address',
                    'tenant',
                    'user',
                ]);

                $html = view('livewire.mall.picking-list', [
                    'order' => $order,
                ])->render();

                $livewire->dispatch('print-ticket', html: $html);

                $this->successNotificationTitle('打印分拣单成功');
                $this->success();
            } catch (Throwable $e) {
                $this->failureNotificationTitle($e->getMessage());
                $this->failure();
            }
        });
    }
}
