<?php

namespace App\Filament\Backend\Clusters\Users\Resources\UserResource\Pages;

use App\Filament\Backend\Clusters\Users\Resources\UserResource;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;
}
