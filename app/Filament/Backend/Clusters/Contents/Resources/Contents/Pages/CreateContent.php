<?php

namespace App\Filament\Backend\Clusters\Contents\Resources\Contents\Pages;

use App\Filament\Backend\Clusters\Contents\Resources\Contents\ContentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;

class CreateContent extends CreateRecord
{
    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('返回')
                ->icon(Heroicon::ArrowLeft)
                ->url(self::$resource::getUrl()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return self::$resource::getUrl();
    }
}
