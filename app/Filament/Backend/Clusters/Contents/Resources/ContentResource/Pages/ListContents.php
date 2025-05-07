<?php

namespace App\Filament\Backend\Clusters\Contents\Resources\ContentResource\Pages;

use App\Filament\Backend\Clusters\Contents\Resources\ContentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContents extends ListRecords
{
    protected static string $resource = ContentResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
