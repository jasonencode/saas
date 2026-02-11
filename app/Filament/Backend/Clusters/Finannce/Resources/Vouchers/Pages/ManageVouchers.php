<?php

namespace App\Filament\Backend\Clusters\Finannce\Resources\Vouchers\Pages;

use App\Filament\Backend\Clusters\Finannce\Resources\Vouchers\VoucherResource;
use Filament\Resources\Pages\ListRecords;

class ManageVouchers extends ListRecords
{
    protected static string $resource = VoucherResource::class;
}
