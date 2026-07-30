<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Comments\Pages;

use App\Filament\Backend\Clusters\Content\Resources\Comments\CommentResource;
use Filament\Resources\Pages\ManageRecords;

class ManageComments extends ManageRecords
{
    protected static string $resource = CommentResource::class;
}
