<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Configures\Schemas;

use Filament\Infolists;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ConfigureInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns()
            ->components([
                Grid::make(1)
                    ->components([
                        Section::make('基础信息')
                            ->components([
                                Infolists\Components\ImageEntry::make('cover')
                                    ->label('店铺LOGO')
                                    ->circular(),
                                Infolists\Components\TextEntry::make('store_name')
                                    ->label('店铺名称'),
                                Infolists\Components\TextEntry::make('tenant.name')
                                    ->label('所属租户')
                                    ->icon(Heroicon::OutlinedBuildingOffice),
                                Infolists\Components\TextEntry::make('store_description')
                                    ->label('店铺描述')
                                    ->placeholder('无')
                                    ->columnSpanFull(),
                            ])->columns(3),
                        Section::make('联系方式')
                            ->components([
                                Infolists\Components\TextEntry::make('contactor')
                                    ->label('联系人')
                                    ->icon(Heroicon::OutlinedUser)
                                    ->placeholder('未设置'),
                                Infolists\Components\TextEntry::make('phone')
                                    ->label('电话')
                                    ->icon(Heroicon::OutlinedPhone)
                                    ->copyable()
                                    ->placeholder('未设置'),
                            ])->columns(),
                    ]),
                Grid::make(1)
                    ->components([
                        Section::make('配置')
                            ->components([
                                Infolists\Components\TextEntry::make('defaultExpress.name')
                                    ->label('默认发货快递')
                                    ->placeholder('未设置'),
                                Infolists\Components\TextEntry::make('auto_complete_days')
                                    ->label('自动完成天数')
                                    ->suffix(' 天')
                                    ->placeholder('未设置'),
                                Infolists\Components\TextEntry::make('order_expired_minutes')
                                    ->label('订单自动取消时间')
                                    ->suffix(' 分钟')
                                    ->placeholder('未设置'),
                            ])->columns(3),
                        Section::make('地址信息')
                            ->components([
                                Infolists\Components\TextEntry::make('province.name')
                                    ->label('省份')
                                    ->placeholder('未设置'),
                                Infolists\Components\TextEntry::make('city.name')
                                    ->label('城市')
                                    ->placeholder('未设置'),
                                Infolists\Components\TextEntry::make('district.name')
                                    ->label('区县')
                                    ->placeholder('未设置'),
                                Infolists\Components\TextEntry::make('address')
                                    ->label('详细地址')
                                    ->placeholder('未设置')
                                    ->columnSpanFull(),
                            ])->columns(3),
                    ]),
            ]);
    }
}
