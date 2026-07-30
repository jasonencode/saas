<?php

namespace App\Filament\Backend\Clusters\Content\Resources\AppVersions\Pages;

use App\Filament\Backend\Clusters\Content\Resources\AppVersions\AppVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageAppVersions extends ManageRecords
{
    protected static string $resource = AppVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
