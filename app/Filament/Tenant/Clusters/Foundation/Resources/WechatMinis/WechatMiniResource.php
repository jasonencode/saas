<?php

namespace App\Filament\Tenant\Clusters\Foundation\Resources\WechatMinis;

use App\Filament\Tenant\Clusters\Foundation\FoundationCluster;
use App\Models\WechatMini;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WechatMiniResource extends Resource
{
    protected static ?string $model = WechatMini::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static ?string $cluster = FoundationCluster::class;

    protected static ?string $modelLabel = '微信小程序';

    protected static ?string $navigationLabel = '微信小程序';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = '微信';

    public static function form(Schema $schema): Schema
    {
        return Schemas\WechatMiniForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\WechatMinisTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageWechatMinis::route('/'),
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
