<?php

namespace App\Filament\Backend\Clusters\User\Resources\Tenants\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Tenant\CloseStoreAction;
use App\Filament\Actions\Tenant\OpenStoreAction;
use App\Filament\Actions\Tenant\RenewalAction;
use App\Filament\Actions\Tenant\SetModulesAction;
use App\Filament\Backend\Clusters\User\Resources\Tenants\TenantResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            RenewalAction::make(),
            SetModulesAction::make(),
            OpenStoreAction::make(),
            CloseStoreAction::make(),
            EditAction::make(),
        ];
    }
}
