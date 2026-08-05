<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\DbLogs\Schemas;

use App\Enums\System\LogLevel;
use App\Filament\Infolists\Components\TextareaEntry;
use Filament\Infolists;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class DbLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)
                    ->schema([
                        Section::make('基本信息')
                            ->icon(Heroicon::InformationCircle)
                            ->columns(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('channel')
                                    ->label('通道')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('level_name')
                                    ->label('级别')
                                    ->badge()
                                    ->color(fn (LogLevel $state): string => $state->getColor()),
                                Infolists\Components\TextEntry::make('datetime')
                                    ->label('发生时间'),
                                Infolists\Components\TextEntry::make('message')
                                    ->label('消息内容')
                                    ->columnSpanFull()
                                    ->markdown(),
                            ]),
                        Section::make('记录信息')
                            ->icon(Heroicon::Clock)
                            ->columns()
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('创建时间'),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('更新时间'),
                            ]),
                    ]),
                Section::make('详细信息')
                    ->icon(Heroicon::DocumentText)
                    ->schema([
                        TextareaEntry::make('context')
                            ->label('上下文 (Context)')
                            ->rows(12)
                            ->placeholder('无上下文数据'),
                        TextareaEntry::make('extra')
                            ->label('附加信息 (Extra)')
                            ->rows(8)
                            ->placeholder('无附加信息'),
                    ])
                    ->collapsible(),
            ]);
    }
}
