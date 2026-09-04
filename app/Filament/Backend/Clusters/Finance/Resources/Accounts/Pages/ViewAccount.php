<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Accounts\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Common\RefreshAction;
use App\Filament\Actions\Finance\AdjustBalanceAction;
use App\Filament\Actions\Finance\AdjustPointsAction;
use App\Filament\Actions\Finance\FreezeBalanceAction;
use App\Filament\Actions\Finance\FreezePointsAction;
use App\Filament\Backend\Clusters\Finance\Resources\Accounts\AccountResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAccount extends ViewRecord
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            RefreshAction::make(),
            AdjustBalanceAction::make(),
            AdjustPointsAction::make(),
            FreezeBalanceAction::make(),
            FreezePointsAction::make(),
        ];
    }
}
