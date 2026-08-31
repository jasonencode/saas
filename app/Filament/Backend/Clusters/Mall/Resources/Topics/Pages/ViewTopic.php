<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Topics\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\Mall\Resources\Topics\TopicResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTopic extends ViewRecord
{
    protected static string $resource = TopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            EditAction::make(),
        ];
    }
}
