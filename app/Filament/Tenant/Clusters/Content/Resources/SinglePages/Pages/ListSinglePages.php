<?php

namespace App\Filament\Tenant\Clusters\Content\Resources\SinglePages\Pages;

use App\Filament\Tenant\Clusters\Content\Resources\SinglePages\SinglePageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSinglePages extends ListRecords
{
    protected static string $resource = SinglePageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
