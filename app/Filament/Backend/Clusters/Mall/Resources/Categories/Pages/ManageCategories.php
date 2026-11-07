<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Categories\Pages;

use App\Filament\Backend\Clusters\Mall\Resources\Categories\CategoryResource;
use Filament\Actions;
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
            Actions\CreateAction::make(),
        ];
    }
}
