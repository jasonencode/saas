<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Certificates;

use App\Filament\Backend\Clusters\BlockChain\BlockChainCluster;
use App\Models\Certificate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = BlockChainCluster::class;

    protected static ?string $modelLabel = '证书';

    protected static ?string $navigationLabel = '证书管理';

    protected static string|UnitEnum|null $navigationGroup = '证书';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return Schemas\CertificateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\CertificatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCertificates::route('/'),
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
