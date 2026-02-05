<?php

namespace App\Filament\Backend\Clusters\User\Resources\Accounts\Pages;

use App\Filament\Backend\Clusters\User\Resources\Accounts\AccountResource;
use Filament\Resources\Pages\ListRecords;

class ListAccounts extends ListRecords
{
    protected static string $resource = AccountResource::class;
}
