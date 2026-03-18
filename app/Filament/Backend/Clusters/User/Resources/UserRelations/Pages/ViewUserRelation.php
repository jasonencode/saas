<?php

namespace App\Filament\Backend\Clusters\User\Resources\UserRelations\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\User\Resources\UserRelations\UserRelationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewUserRelation extends ViewRecord
{
    protected static string $resource = UserRelationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
        ];
    }
}
