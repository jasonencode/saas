<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Payments\Pages;

use App\Filament\Tenant\Clusters\Finance\Resources\Payments\PaymentResource;
use Filament\Resources\Pages\ManageRecords;

class ManagePayments extends ManageRecords
{
    protected static string $resource = PaymentResource::class;
}
