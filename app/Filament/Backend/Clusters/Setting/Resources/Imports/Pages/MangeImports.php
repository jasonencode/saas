<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Imports\Pages;

use App\Filament\Backend\Clusters\Setting\Resources\Imports\ImportResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class MangeImports extends ManageRecords
{
    protected static string $resource = ImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
