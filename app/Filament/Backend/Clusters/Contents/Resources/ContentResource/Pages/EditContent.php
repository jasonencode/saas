<?php

namespace App\Filament\Backend\Clusters\Contents\Resources\ContentResource\Pages;

use App\Filament\Backend\Clusters\Contents\Resources\ContentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContent extends EditRecord
{
    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('back')
                ->label('返回')
                ->icon('heroicon-o-arrow-small-left')
                ->url(self::$resource::getUrl()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return ContentResource::getUrl();
    }
}
