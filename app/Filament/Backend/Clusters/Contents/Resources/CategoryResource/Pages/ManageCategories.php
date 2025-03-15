<?php

namespace App\Filament\Backend\Clusters\Contents\Resources\CategoryResource\Pages;

use App\Filament\Backend\Clusters\Contents\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCategories extends ManageRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
