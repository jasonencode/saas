<?php

namespace App\Filament\Tenant\Clusters\User\Resources\UserRealnames\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\User\ApproveRealnameAction;
use App\Filament\Actions\User\RejectRealnameAction;
use App\Filament\Tenant\Clusters\User\Resources\UserRealnames\UserRealnameResource;
use Filament\Resources\Pages\ViewRecord;

class ViewUserRealname extends ViewRecord
{
    protected static string $resource = UserRealnameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            ApproveRealnameAction::make(),
            RejectRealnameAction::make(),
        ];
    }
}
