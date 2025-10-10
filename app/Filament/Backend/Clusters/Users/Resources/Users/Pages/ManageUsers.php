<?php

namespace App\Filament\Backend\Clusters\Users\Resources\Users\Pages;

use App\Filament\Backend\Clusters\Users\Resources\Users\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
