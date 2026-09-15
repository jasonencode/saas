<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\ScheduleRunLogs\Tables;

use App\Enums\System\ScheduleRunSource;
use App\Enums\System\ScheduleRunStatus;
use App\Models\System\ScheduleRunLog;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class ScheduleRunLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('started_at', 'desc')
            ->poll('30s')
            ->columns([
                Tables\Columns\TextColumn::make('task')
                    ->label('任务')
                    ->badge()
                    ->formatStateUsing(fn (string $state, ScheduleRunLog $record): string => $record->getDisplayName())
                    ->description(fn (ScheduleRunLog $record): string => $record->task)
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (ScheduleRunStatus $state, ScheduleRunLog $record): string => $record->hasErrors() ? 'warning' : $state->getColor())
                    ->description(fn (ScheduleRunLog $record): ?string => $record->hasErrors() ? '有失败明细' : null),
                Tables\Columns\TextColumn::make('source')
                    ->label('来源')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('started_at')
                    ->label('开始时间')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_ms')
                    ->label('耗时')
                    ->formatStateUsing(fn (?int $state): string => ScheduleRunLog::formatDuration($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('server')
                    ->label('执行节点')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options(ScheduleRunStatus::class),
                Tables\Filters\SelectFilter::make('source')
                    ->label('来源')
                    ->options(ScheduleRunSource::class),
                DateRangeFilter::make('started_at')
                    ->label('开始时间')
                    ->teleport()
                    ->timePicker()
                    ->timePicker24()
                    ->timePickerSecond()
                    ->allowInput()
                    ->format('Y-m-d H:i:s'),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make(),
                    Actions\DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
