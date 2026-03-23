<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Configures\Pages;

use App\Filament\Backend\Clusters\Mall\Resources\Configures\ConfigureResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewConfigure extends ViewRecord
{
    protected static string $resource = ConfigureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
