<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\BlackLists\Pages;

use App\Filament\Backend\Clusters\Settings\Resources\BlackLists\BlackListResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBlackLists extends ManageRecords
{
    protected static string $resource = BlackListResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
