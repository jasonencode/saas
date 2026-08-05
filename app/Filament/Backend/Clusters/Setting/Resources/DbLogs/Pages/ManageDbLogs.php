<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\DbLogs\Pages;

use App\Filament\Backend\Clusters\Setting\Resources\DbLogs\DbLogResource;
use Filament\Resources\Pages\ManageRecords;

class ManageDbLogs extends ManageRecords
{
    protected static string $resource = DbLogResource::class;
}
