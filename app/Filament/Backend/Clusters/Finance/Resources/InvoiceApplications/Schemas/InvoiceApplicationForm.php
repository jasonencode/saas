<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\InvoiceApplications\Schemas;

use App\Enums\InvoiceApplicationStatus;
use App\Models\InvoiceTitle;
use Filament\Forms;
use Filament\Schemas\Schema;

class InvoiceApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('invoice_title_id')
                    ->label('发票抬头')
                    ->options(InvoiceTitle::pluck('title', 'id'))
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label('开票金额')
                    ->numeric()
                    ->prefix('¥')
                    ->required(),
                Forms\Components\TextInput::make('reason')
                    ->label('开票原因')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->options(InvoiceApplicationStatus::class)
                    ->required(),
                Forms\Components\Textarea::make('remark')
                    ->label('备注')
                    ->rows(3),
            ]);
    }
}