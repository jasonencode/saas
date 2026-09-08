<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Suggests\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\Content\ToggleSuggestCloseAction;
use App\Filament\Backend\Clusters\Content\Resources\Suggests\SuggestResource;
use Filament\Resources\Pages\ViewRecord;

class ViewSuggest extends ViewRecord
{
    protected static string $resource = SuggestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            ToggleSuggestCloseAction::make(),
        ];
    }
}
