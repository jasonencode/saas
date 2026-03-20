<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\SocialiteAccounts\Pages;

use App\Filament\Backend\Clusters\Foundation\Resources\SocialiteAccounts\SocialiteAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSocialiteAccounts extends ManageRecords
{
    protected static string $resource = SocialiteAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
