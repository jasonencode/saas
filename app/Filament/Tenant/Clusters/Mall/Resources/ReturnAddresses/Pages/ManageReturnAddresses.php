<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\ReturnAddresses\Pages;

use App\Filament\Tenant\Clusters\Mall\Resources\ReturnAddresses\ReturnAddressResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageReturnAddresses extends ManageRecords
{
    protected static string $resource = ReturnAddressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
