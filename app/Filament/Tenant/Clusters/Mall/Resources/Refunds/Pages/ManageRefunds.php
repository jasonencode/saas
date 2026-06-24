<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Refunds\Pages;

use App\Filament\Tenant\Clusters\Mall\Resources\Refunds\RefundResource;
use Filament\Resources\Pages\ManageRecords;

class ManageRefunds extends ManageRecords
{
    protected static string $resource = RefundResource::class;
}
