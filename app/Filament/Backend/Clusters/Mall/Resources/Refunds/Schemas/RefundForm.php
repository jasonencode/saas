<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Refunds\Schemas;

use App\Enums\RefundStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

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

