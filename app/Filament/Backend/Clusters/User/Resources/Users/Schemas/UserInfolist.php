<?php

namespace App\Filament\Backend\Clusters\User\Resources\Users\Schemas;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns()
            ->components([
                Schemas\Components\Fieldset::make('基础信息')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('username')
                            ->label('用户名')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('tenants.name')
                            ->label(__('backend.tenant'))
                            ->badge()
                            ->columnSpan(2),
                    ]),
                Schemas\Components\Fieldset::make('用户信息')
                    ->columns(3)
                    ->schema([
                        Grid::make()
                            ->columnSpan(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('profile.nickname')
                                    ->label('昵称')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('profile.gender')
                                    ->label('性别')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('profile.birthday')
                                    ->label('生日')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('profile.description')
                                    ->label('简介')
                                    ->placeholder('-'),
                            ]),
                        Infolists\Components\ImageEntry::make('profile.avatar')
                            ->label('头像')
                            ->imageSize(90)
                            ->circular(),
                    ]),
            ]);
    }
}
