<?php

namespace App\Filament\Backend\Clusters\User\Resources\Tokens\Pages;

use App\Filament\Backend\Clusters\User\Resources\Tokens\TokenResource;
use Filament\Resources\Pages\ManageRecords;

class ManageTokens extends ManageRecords
{
    protected static string $resource = TokenResource::class;
}
