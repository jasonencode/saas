<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\ScheduleRunLogs\Schemas;

use App\Filament\Infolists\Components\TextareaEntry;
use App\Models\System\ScheduleRunLog;
use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class ScheduleRunLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Fieldset::make('执行信息')
                    ->columns()
                    ->schema([
                        Infolists\Components\TextEntry::make('task')
                            ->label('任务名称')
                            ->formatStateUsing(fn (string $state, ScheduleRunLog $record): string => $record->label())
                            ->copyable(),
                        Infolists\Components\TextEntry::make('expression')
                            ->label('调度表达式')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('server')
                            ->label('执行节点')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('执行状态')
                            ->badge(),
                        Infolists\Components\TextEntry::make('started_at')
                            ->label('开始时间')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('finished_at')
                            ->label('结束时间')
                            ->dateTime()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('duration_ms')
                            ->label('耗时')
                            ->formatStateUsing(fn (?int $state): string => ScheduleRunLog::formatDuration($state)),
                    ]),
                Schemas\Components\Fieldset::make('业务上下文')
                    ->columns()
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('context')
                            ->label('上下文'),
                    ])
                    ->visible(fn (ScheduleRunLog $record): bool => filled($record->context)),
                Schemas\Components\Fieldset::make('失败原因')
                    ->schema([
                        Infolists\Components\TextEntry::make('exception')
                            ->hiddenLabel()
                            ->placeholder('-')
                            ->extraAttributes(['style' => 'font-family: monospace; white-space: pre-wrap;']),
                    ])
                    ->visible(fn (ScheduleRunLog $record): bool => filled($record->exception)),
                Schemas\Components\Fieldset::make('命令输出')
                    ->schema([
                        TextareaEntry::make('output')
                            ->hiddenLabel()
                            ->rows(12),
                    ])
                    ->visible(fn (ScheduleRunLog $record): bool => filled($record->output)),
            ]);
    }
}
