<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\Socialites\Pages;

use App\Filament\Backend\Clusters\Foundation\Resources\Socialites\SocialitesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSocialites extends ManageRecords
{
    protected static string $resource = SocialitesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
