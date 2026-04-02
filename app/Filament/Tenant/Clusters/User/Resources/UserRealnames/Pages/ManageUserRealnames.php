<?php

namespace App\Filament\Tenant\Clusters\User\Resources\UserRealnames\Pages;

use App\Filament\Tenant\Clusters\User\Resources\UserRealnames\UserRealnameResource;
use Filament\Resources\Pages\ManageRecords;

class ManageUserRealnames extends ManageRecords
{
    protected static string $resource = UserRealnameResource::class;
}
