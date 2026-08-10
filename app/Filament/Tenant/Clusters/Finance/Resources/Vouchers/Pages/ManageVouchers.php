<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Vouchers\Pages;

use App\Filament\Tenant\Clusters\Finance\Resources\Vouchers\VoucherResource;
use Filament\Resources\Pages\ManageRecords;

class ManageVouchers extends ManageRecords
{
    protected static string $resource = VoucherResource::class;
}
