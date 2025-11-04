<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Contents\Pages;

use App\Filament\Backend\Clusters\Content\Resources\Contents\ContentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditContent extends EditRecord
{
    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('返回列表')
                ->icon(Heroicon::ArrowLeft)
                ->url(self::$resource::getUrl()),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return self::$resource::getUrl();
    }
}
