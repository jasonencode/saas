<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Expresses\Pages;

use App\Filament\Backend\Clusters\Mall\Resources\Expresses\ExpressResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageExpresses extends ManageRecords
{
    protected static string $resource = ExpressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
