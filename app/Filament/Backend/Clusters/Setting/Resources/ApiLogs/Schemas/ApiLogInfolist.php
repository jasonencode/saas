<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\ApiLogs\Schemas;

use App\Filament\Infolists\Components\TextareaEntry;
use Filament\Infolists;
use Filament\Schemas\Schema;

class ApiLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Infolists\Components\TextEntry::make('user.name')
                    ->label('用户'),
                Infolists\Components\TextEntry::make('method')
                    ->label('请求类型')
                    ->badge(),
                Infolists\Components\TextEntry::make('path')
                    ->label('请求路径'),
                Infolists\Components\TextEntry::make('ip')
                    ->label('IP地址'),
                Infolists\Components\TextEntry::make('user_agent')
                    ->label('UserAgent'),
                Infolists\Components\TextEntry::make('status_code')
                    ->label('返回状态'),
                Infolists\Components\TextEntry::make('duration')
                    ->label('接口耗时'),
                Infolists\Components\TextEntry::make('created_at')
                    ->label('记录时间'),
                TextareaEntry::make('input'),
                TextareaEntry::make('output'),
            ]);
    }
}
