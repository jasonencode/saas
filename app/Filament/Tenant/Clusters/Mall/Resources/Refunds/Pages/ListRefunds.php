<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Refunds\Pages;

use App\Filament\Tenant\Clusters\Mall\Resources\Refunds\RefundResource;
use Filament\Resources\Pages\ListRecords;

class ListRefunds extends ListRecords
{
    protected static string $resource = RefundResource::class;
}
