<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\InvoiceApplications;

use App\Filament\Backend\Clusters\Finance\FinanceCluster;
use App\Models\InvoiceApplication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class InvoiceApplicationResource extends Resource
{
    protected static ?string $model = InvoiceApplication::class;

    protected static string|null|BackedEnum $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $modelLabel = '发票申请';

    protected static ?string $navigationLabel = '发票申请';

    protected static string|null|UnitEnum $navigationGroup = '发票';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return Schemas\InvoiceApplicationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\InvoiceApplicationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\InvoiceApplicationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInvoiceApplications::route('/'),
            'view' => Pages\ViewInvoiceApplication::route('/{record}'),
        ];
    }
}