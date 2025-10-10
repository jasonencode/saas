<?php

namespace App\Filament\Tenant\Clusters\Settings\Resources\Activities;

use App\Filament\Actions\Setting\AuditActivityAction;
use App\Filament\Actions\Setting\AuditActivityBulkAction;
use App\Filament\Tenant\Clusters\Settings\SettingsCluster;
use App\Models\Activity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = SettingsCluster::class;

    public static function table(Table $table): Table
    {
        return $table
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
            ->recordActions([
                AuditActivityAction::make(),
            ])
            ->toolbarActions([
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
