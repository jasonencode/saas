<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Coupons\Pages;

use App\Filament\Actions\Common\BackAction;
use App\Filament\Backend\Clusters\Mall\Resources\Coupons\CouponResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCoupon extends ViewRecord
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackAction::make(),
            Actions\EditAction::make(),
        ];
    }
}

