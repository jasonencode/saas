<?php

namespace App\Filament\Tenant\Clusters\Content\Resources\Categories\Pages;

use App\Filament\Tenant\Clusters\Content\Resources\Categories\CategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Kisame76\FilamentTreeTable\Concerns\InteractsWithExpandableRows;
use Kisame76\FilamentTreeTable\Contracts\HasExpandableRows;

class ManageCategories extends ManageRecords implements HasExpandableRows
{
    use InteractsWithExpandableRows;

    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
