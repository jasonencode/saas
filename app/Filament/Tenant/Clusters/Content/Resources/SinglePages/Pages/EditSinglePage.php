<?php

namespace App\Filament\Tenant\Clusters\Content\Resources\SinglePages\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Common\HeaderSubmitAction;
use App\Filament\Tenant\Clusters\Content\Resources\SinglePages\SinglePageResource;
use Filament\Resources\Pages\EditRecord;

class EditSinglePage extends EditRecord
{
    protected static string $resource = SinglePageResource::class;

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
