<?php

namespace App\Filament\Backend\Clusters\Contents\Resources\Contents\Pages;

use App\Filament\Backend\Clusters\Contents\Resources\Contents\ContentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateContent extends CreateRecord
{
    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('返回')
                ->url(ContentResource::getUrl()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return ContentResource::getUrl();
    }
}
