<?php

namespace App\Filament\Backend\Clusters\Finannce\Resources\PaymentOrders\Pages;

use App\Filament\Backend\Clusters\Finannce\Resources\PaymentOrders\PaymentOrderResource;
use Filament\Resources\Pages\ManageRecords;

class ManagePaymentOrders extends ManageRecords
{
    protected static string $resource = PaymentOrderResource::class;
}
