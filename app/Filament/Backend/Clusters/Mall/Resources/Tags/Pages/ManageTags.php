<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Tags\Pages;

use App\Filament\Backend\Clusters\Mall\Resources\Tags\TagResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTags extends ManageRecords
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
