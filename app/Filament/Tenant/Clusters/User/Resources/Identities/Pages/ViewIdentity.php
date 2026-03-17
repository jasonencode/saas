<?php

namespace App\Filament\Tenant\Clusters\User\Resources\Identities\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Tenant\Clusters\User\Resources\Identities\IdentityResource;
use Filament\Resources\Pages\ViewRecord;

class ViewIdentity extends ViewRecord
{
    protected static string $resource = IdentityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
        ];
    }
}
