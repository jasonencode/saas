<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Tags\Pages;

use App\Filament\Backend\Clusters\Content\Resources\Tags\TagResource;
use Filament\Resources\Pages\ManageRecords;

class ManageTags extends ManageRecords
{
    protected static string $resource = TagResource::class;
}
