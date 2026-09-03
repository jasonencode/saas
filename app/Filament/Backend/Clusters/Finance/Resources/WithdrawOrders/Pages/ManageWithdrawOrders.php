<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\WithdrawOrders\Pages;

use App\Filament\Backend\Clusters\Finance\Resources\WithdrawOrders\WithdrawOrderResource;
use Filament\Resources\Pages\ManageRecords;

class ManageWithdrawOrders extends ManageRecords
{
    protected static string $resource = WithdrawOrderResource::class;
}
