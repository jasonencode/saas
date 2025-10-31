<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\Activities\Tables;

use App\Filament\Actions\Setting\AuditActivityAction;
use App\Filament\Actions\Setting\AuditActivityBulkAction;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->latest())
            ->columns([
                Tables\Columns\TextColumn::make('log_name')
                    ->label('平台'),
                Tables\Columns\TextColumn::make('description')
                    ->label('日志'),
                Tables\Columns\TextColumn::make('subject_type'),
                Tables\Columns\TextColumn::make('subject_id'),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('操作用户'),
                Tables\Columns\TextColumn::make('event'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->recordActions([
                AuditActivityAction::make(),
            ])
            ->toolbarActions([
                AuditActivityBulkAction::make(),
            ]);
    }
}
