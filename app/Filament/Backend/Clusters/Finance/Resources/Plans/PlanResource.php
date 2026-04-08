<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Plans;

use App\Filament\Backend\Clusters\Finance\FinanceCluster;
use App\Models\Finance\Plan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $modelLabel = '结算计划';

    protected static string|UnitEnum|null $navigationGroup = '结算';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return Schemas\PlanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\PlanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\PlansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePlans::route('/'),
            'view' => Pages\ViewPlan::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
