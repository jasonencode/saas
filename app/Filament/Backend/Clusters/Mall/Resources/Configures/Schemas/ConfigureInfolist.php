<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Configures\Schemas;

use App\Enums\Mall\AutoCompleteDays;
use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class ConfigureInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns()
            ->components([
                Schemas\Components\Grid::make(1)
                    ->schema([
                        Schemas\Components\Fieldset::make('基础信息')
                            ->columns(3)
                            ->schema([
                                Schemas\Components\Grid::make()
                                    ->columnSpan(2)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('store_name')
                                            ->label('店铺名称'),
                                        Infolists\Components\TextEntry::make('tenant.name')
                                            ->label(__('backend.tenant'))
                                            ->badge(),
                                        Infolists\Components\TextEntry::make('store_description')
                                            ->label('店铺描述')
                                            ->placeholder('-')
                                            ->columnSpanFull(),
                                    ]),
                                Infolists\Components\ImageEntry::make('cover')
                                    ->label('店铺LOGO')
                                    ->imageSize(90)
                                    ->circular(),
                            ]),
                        Schemas\Components\Fieldset::make('联系方式')
                            ->columns()
                            ->schema([
                                Infolists\Components\TextEntry::make('contactor')
                                    ->label('联系人')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('phone')
                                    ->label('电话')
                                    ->copyable()
                                    ->placeholder('-'),
                            ]),
                    ]),
                Schemas\Components\Grid::make(1)
                    ->schema([
                        Schemas\Components\Fieldset::make('配置')
                            ->columns(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('defaultExpress.name')
                                    ->label('默认发货快递')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('auto_complete_days')
                                    ->label('自动完成天数')
                                    ->formatStateUsing(fn ($state): ?string => $state ? AutoCompleteDays::tryFrom($state)?->getLabel() : null)
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('order_expired_minutes')
                                    ->label('订单自动取消时间')
                                    ->suffix(' 分钟')
                                    ->placeholder('-'),
                            ]),
                        Schemas\Components\Fieldset::make('地址信息')
                            ->columns(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('province.name')
                                    ->label('省份')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('city.name')
                                    ->label('城市')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('district.name')
                                    ->label('区县')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('address')
                                    ->label('详细地址')
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
