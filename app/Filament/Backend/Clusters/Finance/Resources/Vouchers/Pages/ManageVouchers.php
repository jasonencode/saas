<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Vouchers\Pages;

use App\Filament\Backend\Clusters\Finance\Resources\Vouchers\VoucherResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVouchers extends ManageRecords
{
    protected static string $resource = VoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
