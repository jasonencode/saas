<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Orders\Pages;

use App\Filament\Backend\Clusters\Mall\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;

class ManageOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
}

