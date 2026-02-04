<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Coupons\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Backend\Clusters\Mall\Resources\Coupons\CouponResource;

class ViewCoupon extends ViewRecord
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

