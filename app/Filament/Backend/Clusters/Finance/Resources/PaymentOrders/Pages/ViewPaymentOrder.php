<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\PaymentOrders\Pages;

use App\Filament\Backend\Clusters\Finance\Resources\PaymentOrders\PaymentOrderResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentOrder extends ViewRecord
{
    protected static string $resource = PaymentOrderResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->no;
    }
}
