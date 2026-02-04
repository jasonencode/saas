<?php

namespace App\Filament\Tenant\Clusters\Content\Resources\Contents\Pages;

use App\Filament\Tenant\Clusters\Content\Resources\Contents\ContentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContents extends ListRecords
{
    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
