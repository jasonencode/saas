<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\InvoiceApplications\Pages;

use App\Filament\Backend\Clusters\Finance\Resources\InvoiceApplications\InvoiceApplicationResource;
use Filament\Resources\Pages\ListRecords;

class ManageInvoiceApplications extends ListRecords
{
    protected static string $resource = InvoiceApplicationResource::class;
}
