<?php

namespace App\Filament\Backend\Clusters\User\Resources\Identities\Schemas;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class IdentityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Schemas\Components\Section::make('基本信息')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\ImageEntry::make('cover')
                            ->label('封面图'),
                        Infolists\Components\TextEntry::make('tenant.name')
                            ->label('所属租户'),
                        Infolists\Components\TextEntry::make('name')
                            ->label('身份名称'),
                        Infolists\Components\TextEntry::make('price')
                            ->label('订阅价格')
                            ->prefix('¥'),
                        Infolists\Components\TextEntry::make('days')
                            ->label('有效期（天）')
                            ->suffix('天')
                            ->formatStateUsing(fn ($state) => $state === 0 ? '永久' : $state),
                        Infolists\Components\TextEntry::make('sort')
                            ->label('排序'),
                        Infolists\Components\TextEntry::make('description')
                            ->label('简介')
                            ->columnSpanFull(),
                    ]),
                Schemas\Components\Section::make('身份配置')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\IconEntry::make('status')
                            ->label('状态'),
                        Infolists\Components\IconEntry::make('is_default')
                            ->label('默认身份'),
                        Infolists\Components\IconEntry::make('is_unique')
                            ->label('唯一身份'),
                        Infolists\Components\IconEntry::make('can_subscribe')
                            ->label('可订阅'),
                    ]),
                Schemas\Components\Section::make('身份编号')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Infolists\Components\IconEntry::make('serial_open')
                            ->label('开启身份编号'),
                        Infolists\Components\TextEntry::make('serial_prefix')
                            ->label('编号前缀'),
                        Infolists\Components\TextEntry::make('serial_places')
                            ->label('编号位数'),
                        Infolists\Components\TextEntry::make('serial_reserve')
                            ->label('预留编号数量'),
                    ]),
            ]);
    }
}
