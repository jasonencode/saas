<?php

namespace App\Filament\Tenant\Clusters\Foundation\Resources\Wechats;

use App\Filament\Forms\Foundation\WechatForm;
use App\Filament\Infolists\Foundation\WechatInfolist;
use App\Filament\RelationManagers\Foundation\PaymentsRelationManager;
use App\Filament\Tables\Foundation\WechatsTable;
use App\Filament\Tenant\Clusters\Foundation\FoundationCluster;
use App\Models\Foundation\Wechat;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $cluster = FoundationCluster::class;

    protected static ?string $modelLabel = '公众平台';

    protected static ?string $navigationLabel = '公众平台';

    protected static ?int $navigationSort = 1;

    protected static string|null|UnitEnum $navigationGroup = '微信';

    public static function canAccess(): bool
    {
        return FoundationCluster::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return WechatForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WechatInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WechatsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
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
