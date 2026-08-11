<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\Pages;

use App\Enums\Mall\OrderStatus;
use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Mall\CreateRefundAction;
use App\Filament\Actions\Mall\OrderCancelAction;
use App\Filament\Actions\Mall\OrderCompleteAction;
use App\Filament\Actions\Mall\OrderPaymentAction;
use App\Filament\Actions\Mall\OrderPreparingAction;
use App\Filament\Actions\Mall\OrderPrintPickingListAction;
use App\Filament\Actions\Mall\OrderShipAction;
use App\Filament\Actions\Mall\OrderSignAction;
use App\Filament\Tenant\Clusters\Mall\Resources\Orders\OrderResource;
use App\Models\Mall\Order;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

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
            OrderCompleteAction::make(),
            OrderPaymentAction::make(),
            OrderCancelAction::make(),
            CreateRefundAction::make(),
            DeleteAction::make()
                ->visible(fn (Order $record): bool => in_array($record->status, [OrderStatus::Pending, OrderStatus::Canceled], true)),
        ];
    }
}
