<?php

namespace App\Filament\Tenant\Clusters\Settings\Resources\Staffers\Pages;

use App\Filament\Tenant\Clusters\Settings\Resources\Staffers\StafferResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageStaffers extends ManageRecords
{
    protected static string $resource = StafferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
