<?php

namespace App\Filament\Backend\Clusters\User\Resources\Accounts\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Common\RefreshAction;
use App\Filament\Actions\User\AdjustAccountAction;
use App\Filament\Backend\Clusters\User\Resources\Accounts\AccountResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAccount extends ViewRecord
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            RefreshAction::make(),
            AdjustAccountAction::make(),
        ];
    }
}
