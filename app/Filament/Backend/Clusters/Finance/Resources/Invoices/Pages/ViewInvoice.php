<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Invoices\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\Finance\Resources\Invoices\InvoiceResource;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
        ];
    }
}