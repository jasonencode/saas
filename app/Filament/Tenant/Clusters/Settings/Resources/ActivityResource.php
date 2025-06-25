<?php

namespace App\Filament\Tenant\Clusters\Settings\Resources;

use App\Enums\ActivityType;
use App\Filament\Actions\Setting\AuditActivityAction;
use App\Filament\Actions\Setting\AuditActivityBulkAction;
use App\Filament\Tenant\Clusters\Settings;
use App\Filament\Tenant\Clusters\Settings\Resources\ActivityResource\Pages;
use App\Models\Activity;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $modelLabel = '操作日志';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Settings::class;

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->latest())
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('日志'),
                Tables\Columns\TextColumn::make('subject_type'),
                Tables\Columns\TextColumn::make('subject_id'),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('操作用户'),
                Tables\Columns\TextColumn::make('event'),
                Tables\Columns\IconColumn::make('is_audit')
                    ->label('审计'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('操作时间'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_audit')
                    ->label('已审计'),
            ])
            ->actions([
                AuditActivityAction::make(),
            ])
            ->bulkActions([
                AuditActivityBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageActivities::route('/'),
        ];
    }
}
