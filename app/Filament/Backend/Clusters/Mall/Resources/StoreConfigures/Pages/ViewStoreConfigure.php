<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\StoreConfigures\Pages;

use App\Filament\Backend\Clusters\Mall\Resources\StoreConfigures\StoreConfigureResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStoreConfigure extends ViewRecord
{
    protected static string $resource = StoreConfigureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
