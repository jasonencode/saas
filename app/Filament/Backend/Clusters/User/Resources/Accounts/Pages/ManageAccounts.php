<?php

namespace App\Filament\Backend\Clusters\User\Resources\Accounts\Pages;

use App\Filament\Backend\Clusters\User\Resources\Accounts\AccountResource;
use Filament\Resources\Pages\ManageRecords;

class ManageAccounts extends ManageRecords
{
    protected static string $resource = AccountResource::class;
}
