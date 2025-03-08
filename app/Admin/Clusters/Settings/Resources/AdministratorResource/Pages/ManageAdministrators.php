<?php

namespace App\Admin\Clusters\Settings\Resources\AdministratorResource\Pages;

use App\Admin\Clusters\Settings\Resources\AdministratorResource;
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
