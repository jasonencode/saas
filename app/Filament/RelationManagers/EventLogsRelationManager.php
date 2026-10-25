<?php

namespace App\Filament\RelationManagers;

use App\Filament\Tables\Columns\JsonColumn;
use App\Models\System\EventLog;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * 日志中心记录（订单 / 退款单等业务对象通用）
 *
 * 各面板（backend / tenant）的订单与退款单详情页共用本类。
 * 只读展示：日志由 EventLogService 写入，后台不提供新增与删除。
 * 关联是 morphMany + subject_type = 完整类名，见 Models\Traits\HasEventLog。
 */
class EventLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'eventLogs';

    protected static ?string $modelLabel = '事件日志';

    protected static ?string $title = '事件日志';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('actor'))
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('发生时间')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('event')
                    ->label('事件')
                    ->badge(),
                Tables\Columns\TextColumn::make('action')
                    ->label('操作'),
                Tables\Columns\TextColumn::make('actor')
                    ->label('操作者')
                    ->state(fn (EventLog $record): ?string => $record->actorName())
                    ->description(fn (EventLog $record): ?string => $record->actorTypeName())
                    ->placeholder('-'),
                JsonColumn::make('payload')
                    ->label('变更内容'),
                Tables\Columns\TextColumn::make('ip')
                    ->label('IP地址')
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
    }
}
