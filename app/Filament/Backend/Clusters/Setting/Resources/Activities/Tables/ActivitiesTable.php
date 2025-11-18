<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Activities\Tables;

use App\Enums\ActivityType;
use App\Filament\Actions\Setting\AuditActivityAction;
use App\Filament\Actions\Setting\AuditActivityBulkAction;
use Filament\Tables;
use Filament\Tables\Table;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('log_name')
                    ->label('平台')
                    ->visible(isBackend()),
                Tables\Columns\TextColumn::make('description')
                    ->label('日志')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('操作用户')
                    ->searchable(),
                Tables\Columns\TextColumn::make('event'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('平台')
                    ->options(ActivityType::class),
            ])
            ->recordActions([
                AuditActivityAction::make(),
            ])
            ->toolbarActions([
                AuditActivityBulkAction::make(),
            ]);
    }
}
