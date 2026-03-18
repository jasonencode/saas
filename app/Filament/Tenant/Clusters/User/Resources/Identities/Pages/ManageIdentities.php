<?php

namespace App\Filament\Tenant\Clusters\User\Resources\Identities\Pages;

use App\Filament\Tenant\Clusters\User\Resources\Identities\IdentityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageIdentities extends ManageRecords
{
    protected static string $resource = IdentityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
