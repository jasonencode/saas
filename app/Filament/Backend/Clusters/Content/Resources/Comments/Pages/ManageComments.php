<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Comments\Pages;

use App\Filament\Backend\Clusters\Content\Resources\Comments\CommentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ManageComments extends ListRecords
{
    protected static string $resource = CommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
