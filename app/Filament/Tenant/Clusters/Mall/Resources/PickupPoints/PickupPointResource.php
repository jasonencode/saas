<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\PickupPoints;

use App\Filament\Tenant\Clusters\Mall\MallCluster;
use App\Models\Mall\PickupPoint;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PickupPointResource extends Resource
{
    protected static ?string $model = PickupPoint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $cluster = MallCluster::class;

    protected static ?string $modelLabel = '自提点';

    protected static ?int $navigationSort = 15;

    protected static string|null|UnitEnum $navigationGroup = '商品';

    public static function canAccess(): bool
    {
        return MallCluster::isAvailable();
    }

    public static function form(Schema $schema): Schema
    {
        return Schemas\PickupPointForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\PickupPointsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePickupPoints::route('/'),
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
