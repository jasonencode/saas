<?php

namespace App\Filament\Backend\Clusters\User\Resources\UserRelations\Pages;

use App\Filament\Backend\Clusters\User\Resources\UserRelations\UserRelationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ManageUserRelations extends ListRecords
{
    protected static string $resource = UserRelationResource::class;

    protected function getHeaderActions(): array
    {
        return [
//            CreateAction::make(),
        ];
    }
}
