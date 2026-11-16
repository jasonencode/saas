<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\RechargeOrders\Pages;

use App\Filament\Tenant\Clusters\Finance\Resources\RechargeOrders\RechargeOrderResource;
use Filament\Resources\Pages\ManageRecords;

class ManageRechargeOrders extends ManageRecords
{
    protected static string $resource = RechargeOrderResource::class;
}
