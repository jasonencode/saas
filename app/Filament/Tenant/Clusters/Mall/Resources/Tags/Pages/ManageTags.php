<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Tags\Pages;

use App\Filament\Tenant\Clusters\Mall\Resources\Tags\TagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTags extends ManageRecords
{
    protected static string $resource = TagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
