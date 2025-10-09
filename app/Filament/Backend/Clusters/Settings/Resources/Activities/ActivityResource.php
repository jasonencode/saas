<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\Activities;

use App\Filament\Backend\Clusters\Settings\Resources\Activities\Pages\ManageActivities;
use App\Filament\Backend\Clusters\Settings\SettingsCluster;
use App\Models\Activity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $modelLabel = '日志审计';

    protected static string|UnitEnum|null $navigationGroup = '日志';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageActivities::route('/'),
        ];
    }
}
