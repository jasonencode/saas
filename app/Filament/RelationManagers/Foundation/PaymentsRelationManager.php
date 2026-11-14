<?php

namespace App\Filament\RelationManagers\Foundation;

use App\Filament\Forms\Foundation\WechatPaymentForm;
use App\Filament\Tables\Foundation\WechatPaymentsTable;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $modelLabel = '微信支付';

    protected static ?string $title = '微信支付';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return WechatPaymentForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return WechatPaymentsTable::configure($table)
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
