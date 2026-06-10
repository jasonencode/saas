<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Roles\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\Setting\Resources\Roles\RoleResource;
use Filament\Resources\Pages\ViewRecord;

class ViewRole extends ViewRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
        ];
    }
}
