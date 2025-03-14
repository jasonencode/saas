<?php

namespace App\Filament\Backend\Clusters\Contents\Resources\CategoryResource\Pages;

use App\Filament\Backend\Clusters\Contents\Resources\CategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCategories extends ManageRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
