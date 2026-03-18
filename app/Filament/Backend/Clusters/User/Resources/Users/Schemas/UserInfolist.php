<?php

namespace App\Filament\Backend\Clusters\User\Resources\Users\Schemas;

use Filament\Infolists;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(6)
            ->schema([
                Infolists\Components\ImageEntry::make('profile.avatar')
                    ->label('头像')
                    ->circular(),
                Infolists\Components\TextEntry::make('tenant.name')
                    ->label('租户'),
                Infolists\Components\TextEntry::make('username')
                    ->label('用户名')
                    ->copyable(),
                Infolists\Components\TextEntry::make('profile.nickname')
                    ->label('昵称'),
                Infolists\Components\TextEntry::make('profile.birthday')
                    ->label('昵称'),
                Infolists\Components\TextEntry::make('profile.gender')
                    ->label('性别')
                    ->badge(),
            ]);
    }
}
