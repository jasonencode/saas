<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Coupons;

use App\Filament\Tenant\Clusters\Mall\MallCluster;
use App\Models\Coupon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = MallCluster::class;

    protected static ?string $modelLabel = '优惠券';

    protected static ?string $navigationLabel = '优惠券';

    protected static string|null|UnitEnum $navigationGroup = '优惠券';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return Schemas\CouponForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\CouponInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\CouponsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UsersRelationManager::class,
            RelationManagers\ProductsRelationManager::class,
            RelationManagers\OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCoupons::route('/'),
            'view' => Pages\ViewCoupon::route('/{record}'),
        ];
    }
}

