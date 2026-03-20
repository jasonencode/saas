<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Accounts\Pages;

use App\Filament\Backend\Clusters\Finance\Resources\Accounts\AccountResource;
use Filament\Resources\Pages\ManageRecords;

class ManageAccounts extends ManageRecords
{
    protected static string $resource = AccountResource::class;
}
