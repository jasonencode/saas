<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Invoices\Pages;

use App\Filament\Backend\Clusters\Finance\Resources\Invoices\InvoiceResource;
use Filament\Resources\Pages\ListRecords;

class ManageInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;
}
