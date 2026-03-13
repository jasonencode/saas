<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\BlackLists;

use App\Filament\Backend\Clusters\Setting\SettingCluster;
use App\Models\BlackList;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BlackListResource extends Resource
{
    protected static ?string $model = BlackList::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::InboxStack;

    protected static ?string $cluster = SettingCluster::class;

    protected static ?string $modelLabel = 'IP黑名单';

    protected static ?string $navigationLabel = 'IP黑名单';

    protected static ?int $navigationSort = 91;

    protected static string|null|UnitEnum $navigationGroup = 'API';

    public static function form(Schema $schema): Schema
    {
        return Schemas\BlackListForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\BlackListsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBlackLists::route('/'),
        ];
    }
}
