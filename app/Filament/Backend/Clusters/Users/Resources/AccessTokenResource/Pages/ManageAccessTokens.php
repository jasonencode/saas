<?php

namespace App\Filament\Backend\Clusters\Users\Resources\AccessTokenResource\Pages;

use App\Filament\Backend\Clusters\Users\Resources\AccessTokenResource;
use Filament\Resources\Pages\ManageRecords;

class ManageAccessTokens extends ManageRecords
{
    protected static string $resource = AccessTokenResource::class;
}
