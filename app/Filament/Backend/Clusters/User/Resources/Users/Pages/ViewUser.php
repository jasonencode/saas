<?php

namespace App\Filament\Backend\Clusters\User\Resources\Users\Pages;

use App\Filament\Backend\Clusters\User\Resources\Users\UserResource;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;
}
