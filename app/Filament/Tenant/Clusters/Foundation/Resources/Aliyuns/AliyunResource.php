<?php

namespace App\Filament\Tenant\Clusters\Foundation\Resources\Aliyuns;

use App\Filament\Forms\Foundation\AliyunForm;
use App\Filament\Infolists\Foundation\AliyunInfolist;
use App\Filament\RelationManagers\Foundation\DomainsRelationManager;
use App\Filament\RelationManagers\Foundation\EcsRelationManager;
use App\Filament\Tables\Foundation\AliyunsTable;
use App\Filament\Tenant\Clusters\Foundation\FoundationCluster;
use App\Models\Foundation\Aliyun;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AliyunResource extends Resource
{
    protected static ?string $model = Aliyun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloud;

    protected static ?string $cluster = FoundationCluster::class;

    protected static ?string $modelLabel = '阿里云账户';

    protected static ?int $navigationSort = 1;

    protected static string|UnitEnum|null $navigationGroup = '阿里';

    public static function canAccess(): bool
    {
        return FoundationCluster::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return AliyunForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AliyunInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AliyunsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DomainsRelationManager::class,
            EcsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAliyuns::route('/'),
            'view' => Pages\ViewAliyun::route('/{record}'),
            'dns' => Pages\ListDns::route('/{record}/dns/{domain}'),
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
