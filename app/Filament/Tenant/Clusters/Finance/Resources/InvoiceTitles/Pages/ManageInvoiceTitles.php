<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\InvoiceTitles\Pages;

use App\Filament\Tenant\Clusters\Finance\Resources\InvoiceTitles\InvoiceTitleResource;
use Filament\Resources\Pages\ManageRecords;

class ManageInvoiceTitles extends ManageRecords
{
    protected static string $resource = InvoiceTitleResource::class;
}
