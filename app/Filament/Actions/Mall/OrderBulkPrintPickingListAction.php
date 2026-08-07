<?php

namespace App\Filament\Actions\Mall;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Services\Mall\OrderService;
use Filament\Actions\BulkAction;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;
use Throwable;

class OrderBulkPrintPickingListAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'orderBulkPrintPickingList';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('批量打印分拣单');
        $this->icon(Heroicon::OutlinedPrinter);
        $this->color('primary');

        $this->visible(fn (HasTable $livewire): bool => userCan(self::getDefaultName(), $livewire->getTable()->getModel()));

        $this->requiresConfirmation();
        $this->modalHeading('批量打印分拣单');
        $this->modalDescription('将选中的订单合并为一份分拣单文档，适用于联排针式打印机连续走纸。');
        $this->modalSubmitActionLabel('确认打印');
        $this->deselectRecordsAfterCompletion();

        $this->action(fn (Collection $records, Component $livewire) => $this->execute($records, $livewire));
    }

    /**
     * 执行批量打印分拣单
     *
     * @param  Collection<int, Order>  $records
     */
    public function execute(Collection $records, Component $livewire): void
    {
        $orders = $records
            ->filter(fn (Order $order): bool => in_array($order->status, [OrderStatus::Paid, OrderStatus::Preparing], true))
            ->values();

        if ($orders->isEmpty()) {
            $this->failureNotificationTitle('所选订单中没有可打印的订单');
            $this->failure();

            return;
        }

        try {
            foreach ($orders as $order) {
                if ($order->status === OrderStatus::Paid) {
                    service(OrderService::class)
                        ->preparing($order, Filament::auth()->user());
                }
            }

            $orders->loadMissing([
                'items.orderable.product',
                'address',
                'tenant',
                'user',
            ]);

            $html = view('livewire.mall.picking-list-batch', [
                'orders' => $orders,
            ])->render();

            $livewire->dispatch('print-ticket', html: $html);

            $this->successNotificationTitle(sprintf('批量打印分拣单成功（共 %d 单）', $orders->count()));
            $this->success();
        } catch (Throwable $e) {
            $this->failureNotificationTitle($e->getMessage());
            $this->failure();
        }
    }
}
