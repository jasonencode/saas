<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\Wechats;

use App\Filament\Backend\Clusters\Foundation\FoundationCluster;
use App\Models\Wechat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class WechatResource extends Resource
{
    protected static ?string $model = Wechat::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = FoundationCluster::class;

    protected static ?string $modelLabel = '公众平台';

    protected static ?string $navigationLabel = '公众平台';

    protected static ?int $navigationSort = 1;

    protected static string|UnitEnum|null $navigationGroup = '微信';

    public static function form(Schema $schema): Schema
    {
        return Schemas\WechatForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\WechatInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\WechatsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageWechats::route('/'),
            'view' => Pages\ViewWechat::route('/{record}'),
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
