<?php

namespace App\Filament\Tenant\Clusters\Settings\Resources\StafferResource\Pages;

use App\Filament\Tenant\Clusters\Settings\Resources\StafferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStaffers extends ManageRecords
{
    protected static string $resource = StafferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
