<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\InvoiceApplications\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\Finance\Resources\InvoiceApplications\InvoiceApplicationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoiceApplication extends ViewRecord
{
    protected static string $resource = InvoiceApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
        ];
    }
}