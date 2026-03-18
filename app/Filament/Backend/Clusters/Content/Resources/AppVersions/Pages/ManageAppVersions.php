<?php

namespace App\Filament\Backend\Clusters\Content\Resources\AppVersions\Pages;

use App\Filament\Backend\Clusters\Content\Resources\AppVersions\AppVersionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAppVersions extends ManageRecords
{
    protected static string $resource = AppVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
