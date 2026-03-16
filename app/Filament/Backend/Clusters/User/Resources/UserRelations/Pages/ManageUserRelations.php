<?php

namespace App\Filament\Backend\Clusters\User\Resources\UserRelations\Pages;

use App\Filament\Backend\Clusters\User\Resources\UserRelations\UserRelationResource;
use Filament\Resources\Pages\ManageRecords;

class ManageUserRelations extends ManageRecords
{
    protected static string $resource = UserRelationResource::class;
}
