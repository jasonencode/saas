<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Common\RefreshAction;
use App\Filament\Actions\Mall\OrderAddRemarkAction;
use App\Filament\Actions\Mall\OrderCancelAction;
use App\Filament\Actions\Mall\OrderCompleteAction;
use App\Filament\Actions\Mall\OrderModifyAddressAction;
use App\Filament\Actions\Mall\OrderPreparingAction;
use App\Filament\Actions\Mall\OrderPrintPickingListAction;
use App\Filament\Actions\Mall\OrderPrintShippingAction;
use App\Filament\Actions\Mall\OrderShipAction;
use App\Filament\Actions\Mall\OrderSignAction;
use App\Filament\Actions\Mall\OrderVirtualPaymentAction;
use App\Filament\Tenant\Clusters\Mall\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function resolveRecord(int|string $key): Model
    {
        return parent::resolveRecord($key)->load('items');
    }

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            RefreshAction::make(),
            OrderPreparingAction::make(),
            OrderShipAction::make(),
            OrderSignAction::make(),
            OrderCompleteAction::make(),
            OrderCancelAction::make(),
            OrderVirtualPaymentAction::make(),
            OrderModifyAddressAction::make(),
            OrderAddRemarkAction::make(),
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
