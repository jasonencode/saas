<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\DbLogs\Schemas;

use Filament\Infolists;
use Filament\Schemas\Schema;

class DbLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Infolists\Components\TextEntry::make('channel')
                    ->label('通道'),
                Infolists\Components\TextEntry::make('level_name')
                    ->label('级别')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'DEBUG' => 'gray',
                        'INFO' => 'info',
                        'NOTICE' => 'info',
                        'WARNING' => 'warning',
                        'ERROR' => 'danger',
                        'CRITICAL' => 'danger',
                        'ALERT' => 'danger',
                        'EMERGENCY' => 'danger',
                        default => 'gray',
                    }),
                Infolists\Components\TextEntry::make('message')
                    ->label('消息')
                    ->columnSpanFull(),
                Infolists\Components\TextEntry::make('datetime')
                    ->label('时间'),
                Infolists\Components\TextEntry::make('context')
                    ->label('上下文')
                    ->columnSpanFull()
                    ->placeholder('无'),
                Infolists\Components\TextEntry::make('extra')
                    ->label('附加信息')
                    ->columnSpanFull()
                    ->placeholder('无'),
                Infolists\Components\TextEntry::make('created_at')
                    ->label('创建时间'),
            ]);
    }
}
