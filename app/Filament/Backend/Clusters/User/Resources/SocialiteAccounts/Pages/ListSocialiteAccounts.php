<?php

namespace App\Filament\Backend\Clusters\User\Resources\SocialiteAccounts\Pages;

use App\Filament\Backend\Clusters\User\Resources\SocialiteAccounts\SocialiteAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSocialiteAccounts extends ListRecords
{
    protected static string $resource = SocialiteAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
