<?php

namespace App\Admin\Resources\OperationLogResource\Pages;

use App\Admin\Resources\OperationLogResource;
use Filament\Resources\Pages\ManageRecords;

class ManageOperationLogs extends ManageRecords
{
    protected static string $resource = OperationLogResource::class;
}
