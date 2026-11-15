<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\Pages;

use App\Enums\Mall\OrderStatus;
use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Mall\CreateRefundAction;
use App\Filament\Actions\Mall\OrderAddRemarkAction;
use App\Filament\Actions\Mall\OrderCancelAction;
use App\Filament\Actions\Mall\OrderCancelDeliverAction;
use App\Filament\Actions\Mall\OrderCompleteAction;
use App\Filament\Actions\Mall\OrderDelaySignAction;
use App\Filament\Actions\Mall\OrderModifyAddressAction;
use App\Filament\Actions\Mall\OrderPaymentAction;
use App\Filament\Actions\Mall\OrderPreparingAction;
use App\Filament\Actions\Mall\OrderPrintPickingListAction;
use App\Filament\Actions\Mall\OrderShipAction;
use App\Filament\Actions\Mall\OrderSignAction;
use App\Filament\Actions\Mall\OrderUpdateExpressAction;
use App\Filament\Actions\Mall\OrderUpdateFreightAction;
use App\Filament\Tenant\Clusters\Mall\Resources\Orders\OrderResource;
use App\Models\Mall\Order;
use App\Services\Mall\OrderService;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ViewRecord;
use InvalidArgumentException;
use RuntimeException;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return '订单详情';
    }

    public function getSubheading(): string
    {
        return $this->getRecord()->no;
    }

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            OrderPreparingAction::make(),
            OrderPrintPickingListAction::make(),
            OrderShipAction::make(),
            OrderSignAction::make(),
            OrderDelaySignAction::make(),
            OrderUpdateExpressAction::make(),
            OrderCancelDeliverAction::make(),
            OrderUpdateFreightAction::make(),
            OrderCompleteAction::make(),
            OrderPaymentAction::make(),
            OrderCancelAction::make(),
            CreateRefundAction::make(),
            OrderModifyAddressAction::make(),
            OrderAddRemarkAction::make(),
            DeleteAction::make()
                ->visible(fn (Order $record): bool => in_array($record->status, OrderStatus::deletable(), true) && userCan('delete', $record))
                ->action(function (Order $record): void {
                    try {
                        service(OrderService::class)->delete($record, Filament::auth()->user());

                        $this->successNotificationTitle('订单已删除');
                        $this->success();
                        $this->redirect(OrderResource::getUrl('index'));
                    } catch (InvalidArgumentException|RuntimeException $e) {
                        $this->failureNotificationTitle('删除失败');
                        $this->failureNotificationBody($e->getMessage());
                        $this->failure();
                    }
                }),
        ];
    }
}
