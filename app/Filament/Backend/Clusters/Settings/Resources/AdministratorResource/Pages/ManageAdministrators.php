<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\AdministratorResource\Pages;

use App\Filament\Backend\Clusters\Settings\Resources\AdministratorResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAdministrators extends ManageRecords
{
    protected static string $resource = AdministratorResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
