<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\InvoiceTitles\Pages;

use App\Filament\Backend\Clusters\Finance\Resources\InvoiceTitles\InvoiceTitleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageInvoiceTitles extends ManageRecords
{
    protected static string $resource = InvoiceTitleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
