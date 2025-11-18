<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Tokens\Pages;

use App\Filament\Backend\Clusters\Setting\Resources\Tokens\TokenResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ManageTokens extends ListRecords
{
    protected static string $resource = TokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
