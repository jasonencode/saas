<?php

namespace App\Filament\Backend\Clusters\Users\Resources\UserResource\Pages;

use App\Filament\Backend\Clusters\Users\Resources\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
