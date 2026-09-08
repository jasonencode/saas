<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Suggests\Pages;

use App\Filament\Backend\Clusters\Content\Resources\Suggests\SuggestResource;
use Filament\Resources\Pages\ListRecords;

class ListSuggests extends ListRecords
{
    protected static string $resource = SuggestResource::class;
}
