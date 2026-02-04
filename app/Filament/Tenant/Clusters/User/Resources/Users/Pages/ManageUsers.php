<?php

namespace App\Filament\Tenant\Clusters\User\Resources\Users\Pages;

use App\Filament\Tenant\Clusters\User\Resources\Users\UserResource;
use Filament\Resources\Pages\ListRecords;

class ManageUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
}
