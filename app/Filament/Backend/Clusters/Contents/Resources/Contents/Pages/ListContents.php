<?php

namespace App\Filament\Backend\Clusters\Contents\Resources\Contents\Pages;

use App\Filament\Backend\Clusters\Contents\Resources\Contents\ContentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContents extends ListRecords
{
    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
