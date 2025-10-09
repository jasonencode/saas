<?php

namespace App\Filament\Backend\Clusters\Users\Resources\Tokens\Pages;

use App\Filament\Backend\Clusters\Users\Resources\Tokens\TokenResource;
use Filament\Resources\Pages\ManageRecords;

class ManageTokens extends ManageRecords
{
    protected static string $resource = TokenResource::class;
}
