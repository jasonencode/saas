<?php

namespace App\Filament\Tenant\Clusters\Content\Resources\Contents\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Common\HeaderSubmitAction;
use App\Filament\Tenant\Clusters\Content\Resources\Contents\ContentResource;
use Filament\Resources\Pages\EditRecord;

class EditContent extends EditRecord
{
    protected static string $resource = ContentResource::class;

    public function getRelationManagers(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            HeaderSubmitAction::make(),
        ];
    }
}
