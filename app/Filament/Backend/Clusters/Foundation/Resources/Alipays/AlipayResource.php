<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\Alipays;

use App\Filament\Backend\Clusters\Foundation\FoundationCluster;
use App\Models\Alipay;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AlipayResource extends Resource
{
    protected static ?string $model = Alipay::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $cluster = FoundationCluster::class;

    protected static ?string $modelLabel = '支付宝';

    protected static ?string $navigationLabel = '支付宝';

    protected static ?int $navigationSort = 1;

    protected static string|null|UnitEnum $navigationGroup = '支付宝';

    public static function form(Schema $schema): Schema
    {
        return Schemas\AlipayForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\AlipaysTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAlipays::route('/'),
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
