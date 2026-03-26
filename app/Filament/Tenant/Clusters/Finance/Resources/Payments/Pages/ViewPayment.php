<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Payments\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Tenant\Clusters\Finance\Resources\Payments\PaymentResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
        ];
    }
}
