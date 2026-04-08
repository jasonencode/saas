<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Invoices;

use App\Filament\Tenant\Clusters\Finance\FinanceCluster;
use App\Models\Finance\Invoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|null|BackedEnum $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $modelLabel = '发票管理';

    protected static ?string $navigationLabel = '发票管理';

    protected static string|null|UnitEnum $navigationGroup = '发票';

    protected static ?int $navigationSort = 15;

    public static function table(Table $table): Table
    {
        return Tables\InvoicesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\InvoiceInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInvoices::route('/'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }
}