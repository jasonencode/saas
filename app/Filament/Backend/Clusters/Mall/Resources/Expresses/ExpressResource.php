<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Expresses;

use App\Filament\Backend\Clusters\Mall\MallCluster;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\Express;
use UnitEnum;

class ExpressResource extends Resource
{
    protected static ?string $model = Express::class;

    protected static ?string $cluster = MallCluster::class;

    protected static ?string $modelLabel = '快递';

    protected static ?string $navigationLabel = '快递管理';

    protected static string|null|UnitEnum $navigationGroup = '基础配置';

    protected static ?int $navigationSort = 30;

    public static function canAccess(): bool
    {
        return isBackend();
    }

    public static function form(Schema $schema): Schema
    {
        return Schemas\ExpressForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\ExpressesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageExpresses::route('/'),
        ];
    }
}

