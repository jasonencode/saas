<?php

namespace App\Filament\Backend\Clusters\User\Resources\Socialites\Pages;

use App\Filament\Backend\Clusters\User\Resources\Socialites\SocialitesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSocialites extends ListRecords
{
    protected static string $resource = SocialitesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
