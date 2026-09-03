<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\RechargeOrders\Pages;

use App\Filament\Backend\Clusters\Finance\Resources\RechargeOrders\RechargeOrderResource;
use Filament\Resources\Pages\ManageRecords;

class ManageRechargeOrders extends ManageRecords
{
    protected static string $resource = RechargeOrderResource::class;
}
