<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Deliveries;

use App\Filament\Tenant\Clusters\Mall\MallCluster;
use App\Models\Mall\Delivery;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DeliveryResource extends Resource
{
    protected static ?string $model = Delivery::class;

    protected static ?string $cluster = MallCluster::class;

    protected static ?string $modelLabel = '运费模板';

    protected static ?string $navigationLabel = '运费模板管理';

    protected static ?int $navigationSort = 37;

    protected static string|null|UnitEnum $navigationGroup = '基础配置';

    public static function canAccess(): bool
    {
        return MallCluster::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return Schemas\DeliveryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\DeliveriesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\DeliveryInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DeliveryRulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDeliveries::route('/'),
            'view' => Pages\ViewDelivery::route('/{record}'),
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
