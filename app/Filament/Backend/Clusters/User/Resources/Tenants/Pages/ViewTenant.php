<?php

namespace App\Filament\Backend\Clusters\User\Resources\Tenants\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\User\Resources\Tenants\TenantResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    protected function getActions(): array
    {
        return [
            BackAction::make(),
            EditAction::make(),
        ];
    }
}
