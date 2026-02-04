<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\Pages;

use App\Filament\Tenant\Clusters\Mall\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;

class ManageOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
}

