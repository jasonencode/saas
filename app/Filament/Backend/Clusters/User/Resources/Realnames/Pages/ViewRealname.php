<?php

namespace App\Filament\Backend\Clusters\User\Resources\Realnames\Pages;

use App\Filament\Actions\User\ApproveRealnameAction;
use App\Filament\Actions\User\RejectRealnameAction;
use App\Filament\Backend\Clusters\User\Resources\Realnames\RealnameResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewRealname extends ViewRecord
{
    protected static string $resource = RealnameResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    protected function getHeaderActions(): array
    {
        return [
            ApproveRealnameAction::make(),
            RejectRealnameAction::make(),
        ];
    }
}
