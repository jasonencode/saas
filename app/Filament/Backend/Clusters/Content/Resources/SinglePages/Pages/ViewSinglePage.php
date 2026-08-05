<?php

namespace App\Filament\Backend\Clusters\Content\Resources\SinglePages\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\Content\Resources\SinglePages\SinglePageResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSinglePage extends ViewRecord
{
    protected static string $resource = SinglePageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
