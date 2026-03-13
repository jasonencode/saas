<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\PaymentOrders\Pages;

use App\Filament\Backend\Clusters\Finance\Resources\PaymentOrders\PaymentOrderResource;
use Filament\Resources\Pages\ManageRecords;

class ManagePaymentOrders extends ManageRecords
{
    protected static string $resource = PaymentOrderResource::class;
}
