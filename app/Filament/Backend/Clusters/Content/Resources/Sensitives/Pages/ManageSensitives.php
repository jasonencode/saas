<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Sensitives\Pages;

use App\Filament\Actions\Content\BatchCreateSensitiveAction;
use App\Filament\Backend\Clusters\Content\Resources\Sensitives\SensitiveResource;
use Filament\Resources\Pages\ManageRecords;

class ManageSensitives extends ManageRecords
{
    protected static string $resource = SensitiveResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BatchCreateSensitiveAction::make(),
        ];
    }
}
