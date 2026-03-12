<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Contents\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\Content\Resources\Contents\ContentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContent extends ViewRecord
{
    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}