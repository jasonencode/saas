<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Brands\Pages;

use App\Filament\Backend\Clusters\Mall\Resources\Brands\BrandResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBrands extends ManageRecords
{
    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

