<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Deliveries\Schemas;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class DeliveryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Section::make('基本信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('模板名称'),
                        Infolists\Components\TextEntry::make('type')
                            ->label('计费方式')
                            ->badge(),
                        Infolists\Components\TextEntry::make('status')
                            ->label('状态')
                            ->formatStateUsing(fn ($state) => $state ? '启用' : '禁用')
                            ->color(fn ($state) => $state ? 'success' : 'danger')
                            ->badge(),
                        Infolists\Components\TextEntry::make('is_default')
                            ->label('默认模板')
                            ->formatStateUsing(fn ($state) => $state ? '是' : '否')
                            ->color(fn ($state) => $state ? 'success' : 'gray')
                            ->badge(),
                    ])
                    ->columns(),
                Schemas\Components\Section::make('运费配置')
                    ->schema([
                        Infolists\Components\TextEntry::make('first')
                            ->label('首件/首重'),
                        Infolists\Components\TextEntry::make('first_fee')
                            ->label('首费(元)')
                            ->prefix('¥'),
                        Infolists\Components\TextEntry::make('additional')
                            ->label('续件/续重'),
                        Infolists\Components\TextEntry::make('additional_fee')
                            ->label('续费(元)')
                            ->prefix('¥'),
                        Infolists\Components\TextEntry::make('free_shipping_threshold')
                            ->label('包邮门槛(元)')
                            ->prefix('¥'),
                    ])
                    ->columns(3),
            ]);
    }
}
