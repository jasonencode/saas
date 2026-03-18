<?php

namespace App\Filament\Backend\Clusters\User\Resources\Users\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\User\Resources\Users\UserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            EditAction::make(),
        ];
    }
}
