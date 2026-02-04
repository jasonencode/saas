<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Contents\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\Content\Resources\Contents\ContentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContent extends CreateRecord
{
    protected static string $resource = ContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return self::$resource::getUrl();
    }
}
