<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Refunds\Pages;

use App\Filament\Tenant\Clusters\Mall\Resources\Refunds\RefundResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRefund extends CreateRecord
{
    protected static string $resource = RefundResource::class;
}
