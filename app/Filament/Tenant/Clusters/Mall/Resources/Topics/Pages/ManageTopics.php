<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Topics\Pages;

use App\Filament\Tenant\Clusters\Mall\Resources\Topics\TopicResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTopics extends ManageRecords
{
    protected static string $resource = TopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
