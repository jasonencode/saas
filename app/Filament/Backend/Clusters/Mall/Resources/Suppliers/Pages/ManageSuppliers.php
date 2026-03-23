<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Suppliers\Pages;

use App\Filament\Backend\Clusters\Mall\Resources\Suppliers\SupplierResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSuppliers extends ManageRecords
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
