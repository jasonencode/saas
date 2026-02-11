<?php

namespace App\Filament\Backend\Clusters\Finannce\Resources\Plans\Pages;

use App\Filament\Backend\Clusters\Finannce\Resources\Plans\PlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ManagePlans extends ListRecords
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
