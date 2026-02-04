<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Coupons\Schemas;

use Filament\Infolists;
use Filament\Schemas\Schema;

class CouponInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Infolists\Components\TextEntry::make('name')
                    ->label('优惠券名称'),
                Infolists\Components\TextEntry::make('code')
                    ->label('优惠券代码'),
                Infolists\Components\TextEntry::make('type')
                    ->label('优惠券类型')
                    ->badge(),
            ]);
    }
}

