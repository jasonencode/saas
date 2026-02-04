<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Refunds\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use App\Enums\RefundStatus;

class RefundForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('no')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'id')
                    ->required(),
                Select::make('store_id')
                    ->relationship('store', 'id')
                    ->required(),
                TextInput::make('order_id')
                    ->required()
                    ->numeric(),
                TextInput::make('total')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('status')
                    ->options(RefundStatus::class)
                    ->default('pending')
                    ->required(),
                DateTimePicker::make('refund_at'),
            ]);
    }
}

