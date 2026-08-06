<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Refunds;

use App\Filament\Tenant\Clusters\Mall\MallCluster;
use App\Models\Mall\Refund;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class RefundResource extends Resource
{
    protected static ?string $model = Refund::class;

    protected static ?string $cluster = MallCluster::class;

    protected static ?string $modelLabel = '退款';

    protected static ?string $navigationLabel = '退款管理';

    protected static string|UnitEnum|null $navigationGroup = '订单';

    protected static ?int $navigationSort = 22;

    public static function canAccess(): bool
    {
        return MallCluster::isAvailable();
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\RefundInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\RefundsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
            RelationManagers\LogsRelationManager::class,
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRefunds::route('/'),
            'view' => Pages\ViewRefund::route('/{record}'),
        ];
    }
}
