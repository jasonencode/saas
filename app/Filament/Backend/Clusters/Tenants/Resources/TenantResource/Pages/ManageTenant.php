<?php

namespace App\Filament\Backend\Clusters\Tenants\Resources\TenantResource\Pages;

use App\Filament\Backend\Clusters\Tenants\Resources\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTenant extends ManageRecords
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
