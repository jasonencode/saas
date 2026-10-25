<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\EventLogs\Schemas;

use App\Models\System\EventLog;
use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class EventLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Fieldset::make('基本信息')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('event')
                            ->label('事件')
                            ->badge()
                            ->copyable(),
                        Infolists\Components\TextEntry::make('action')
                            ->label('操作'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('发生时间')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('actor')
                            ->label('操作者')
                            ->state(fn (EventLog $record): ?string => $record->actorName())
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('actor_type')
                            ->label('操作者类型')
                            ->state(fn (EventLog $record): ?string => $record->actorTypeName())
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('subject_type')
                            ->label('业务对象')
                            ->state(fn (EventLog $record): ?string => $record->subject_type
                                ? class_basename($record->subject_type).' #'.$record->subject_id
                                : null)
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('description')
                            ->label('说明')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('ip')
                            ->label('IP地址')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('request_id')
                            ->label('追踪ID')
                            ->copyable()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('user_agent')
                            ->label('User-Agent')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Schemas\Components\Fieldset::make('变更内容')
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('payload')
                            ->hiddenLabel()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (EventLog $record): bool => filled($record->payload)),
            ]);
    }
}
