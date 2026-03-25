<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Common\RefreshAction;
use App\Filament\Actions\Mall\OrderPrintPickingListAction;
use App\Filament\Actions\Mall\OrderPrintShippingAction;
use App\Filament\Actions\Mall\OrderShipAction;
use App\Filament\Actions\Mall\OrderVirtualPaymentAction;
use App\Filament\Tenant\Clusters\Mall\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            RefreshAction::make(),
            OrderVirtualPaymentAction::make(),
            OrderShipAction::make(),
            OrderPrintPickingListAction::make(),
            OrderPrintShippingAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return '订单详情';
    }

    public function getSubheading(): string
    {
        return $this->getRecord()->no;
    }
}
