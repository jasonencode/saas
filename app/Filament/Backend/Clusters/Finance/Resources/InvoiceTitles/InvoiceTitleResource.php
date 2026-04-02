<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\InvoiceTitles;

use App\Filament\Backend\Clusters\Finance\FinanceCluster;
use App\Models\InvoiceTitle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class InvoiceTitleResource extends Resource
{
    protected static ?string $model = InvoiceTitle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $modelLabel = '发票抬头';

    protected static ?string $navigationLabel = '发票抬头';

    protected static ?int $navigationSort = 55;

    protected static string|UnitEnum|null $navigationGroup = '账户';

    public static function form(Schema $schema): Schema
    {
        return Schemas\InvoiceTitleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\InvoiceTitlesTable::configure($table);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInvoiceTitles::route('/'),
            'view' => Pages\ViewInvoiceTitle::route('/{record}'),
        ];
    }
}
