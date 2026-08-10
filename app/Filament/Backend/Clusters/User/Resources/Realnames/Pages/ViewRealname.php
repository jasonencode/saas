<?php

namespace App\Filament\Backend\Clusters\User\Resources\Realnames\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Actions\User\ApproveRealnameAction;
use App\Filament\Actions\User\RejectRealnameAction;
use App\Filament\Backend\Clusters\User\Resources\Realnames\RealnameResource;
use Filament\Resources\Pages\ViewRecord;

class ViewRealname extends ViewRecord
{
    protected static string $resource = RealnameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            ApproveRealnameAction::make(),
            RejectRealnameAction::make(),
        ];
    }
}
