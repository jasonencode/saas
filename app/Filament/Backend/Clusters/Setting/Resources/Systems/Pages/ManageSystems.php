<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Systems\Pages;

use App\Filament\Backend\Clusters\Setting\Resources\Systems\SystemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSystems extends ManageRecords
{
    protected static string $resource = SystemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
