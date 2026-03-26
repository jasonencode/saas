<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Accounts\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Tenant\Clusters\Finance\Resources\Accounts\AccountResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAccount extends ViewRecord
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
        ];
    }
}
