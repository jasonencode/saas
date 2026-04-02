<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\InvoiceTitles\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Tenant\Clusters\Finance\Resources\InvoiceTitles\InvoiceTitleResource;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoiceTitle extends ViewRecord
{
    protected static string $resource = InvoiceTitleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
        ];
    }
}
