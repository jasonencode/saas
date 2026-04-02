<?php

namespace App\Filament\Tenant\Clusters\User\Resources\UserRealnames\Pages;

use App\Filament\Actions\User\ApproveRealnameAction;
use App\Filament\Actions\User\RejectRealnameAction;
use App\Filament\Tenant\Clusters\User\Resources\UserRealnames\UserRealnameResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewUserRealname extends ViewRecord
{
    protected static string $resource = UserRealnameResource::class;

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
