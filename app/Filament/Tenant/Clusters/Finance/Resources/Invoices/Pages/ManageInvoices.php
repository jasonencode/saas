<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Invoices\Pages;

use App\Filament\Tenant\Clusters\Finance\Resources\Invoices\InvoiceResource;
use Filament\Resources\Pages\ListRecords;

class ManageInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;
}