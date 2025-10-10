<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\Administrators\Pages;

use App\Filament\Backend\Clusters\Settings\Resources\Administrators\AdministratorResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAdministrators extends ManageRecords
{
    protected static string $resource = AdministratorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
