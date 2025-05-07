<?php

namespace App\Filament\Backend\Clusters\Contents\Resources\ContentResource\Pages;

use App\Filament\Backend\Clusters\Contents\Resources\ContentResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContent extends EditRecord
{
    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('返回列表')
                ->icon('heroicon-o-arrow-small-left')
                ->url(self::$resource::getUrl()),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return ContentResource::getUrl();
    }
}
