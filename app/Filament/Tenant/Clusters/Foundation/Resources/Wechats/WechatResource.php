<?php

namespace App\Filament\Tenant\Clusters\Foundation\Resources\Wechats;

use App\Filament\Tenant\Clusters\Foundation\FoundationCluster;
use App\Models\Wechat;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WechatResource extends Resource
{
    protected static ?string $model = Wechat::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = FoundationCluster::class;

    protected static ?string $modelLabel = '公众平台';

    protected static ?string $navigationLabel = '公众平台';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return Schemas\WechatForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\WechatsTable::configure($table);
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
