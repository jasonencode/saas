<?php

namespace App\Filament\Backend\Clusters\Tenants\Resources\Tenants\Pages;

use App\Filament\Backend\Clusters\Tenants\Resources\Tenants\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTenants extends ManageRecords
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
