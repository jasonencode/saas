<?php

namespace App\Filament\Tenant\Clusters\User\Resources\Users\Pages;

use App\Filament\Tenant\Clusters\User\Resources\Users\UserResource;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;
}
