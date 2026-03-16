<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Plans\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\Finance\Resources\Plans\PlanResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPlan extends ViewRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            EditAction::make(),
        ];
    }
}
