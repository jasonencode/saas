<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\EventLogs\Tables;

use App\Models\Mall\Order;
use App\Models\Mall\Refund;
use App\Models\System\Administrator;
use App\Models\System\EventLog;
use App\Models\System\System;
use App\Models\User\User;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class EventLogsTable
{
    public static function configure(Table $table): Table
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
                    ->badge()
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('action')
                    ->label('操作')
                    ->searchable(),
                Tables\Columns\TextColumn::make('actor')
                    ->label('操作者')
                    ->state(fn (EventLog $record): ?string => $record->actorName())
                    ->description(fn (EventLog $record): ?string => $record->actorTypeName())
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('业务对象')
                    ->state(fn (EventLog $record): ?string => $record->subject_type
                        ? class_basename($record->subject_type).' #'.$record->subject_id
                        : null)
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('说明')
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ip')
                    ->label('IP地址')
                    ->copyable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('request_id')
                    ->label('追踪ID')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('actor_type')
                    ->label('操作者类型')
                    ->options([
                        User::class => '用户',
                        Administrator::class => '管理员',
                        System::class => '系统',
                    ]),
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('业务对象')
                    ->options([
                        Order::class => '订单',
                        Refund::class => '退款单',
                    ]),
                Tables\Filters\Filter::make('event')
                    ->schema([
                        TextInput::make('event')
                            ->label('事件标识（前缀匹配）'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['event'] ?? null,
                        fn (Builder $query, string $event): Builder => $query->where('event', 'like', $event.'%'),
                    )),
                Tables\Filters\Filter::make('request_id')
                    ->schema([
                        TextInput::make('request_id')
                            ->label('追踪ID'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['request_id'] ?? null,
                        fn (Builder $query, string $requestId): Builder => $query->where('request_id', $requestId),
                    )),
                DateRangeFilter::make('created_at')
                    ->label('发生时间')
                    ->format('Y-m-d H:i')
                    ->placeholder('开始日期 - 结束日期'),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
            ]);
    }
}
