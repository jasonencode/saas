<?php

namespace App\Filament\Tenant\Clusters\Settings\Resources\Roles\Pages;

use App\Filament\Tenant\Clusters\Settings\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRoles extends ManageRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
