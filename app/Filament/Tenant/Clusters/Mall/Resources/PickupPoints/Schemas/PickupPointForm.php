<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\PickupPoints\Schemas;

use App\Filament\Forms\Components\AddressSelect;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;

class PickupPointForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('自提点名称')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact')
                    ->label('联系人')
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('联系电话')
                    ->maxLength(32),
                AddressSelect::make(),
                Schemas\Components\Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Toggle::make('status')
                            ->label(__('backend.status'))
                            ->default(true),
                        Forms\Components\TextInput::make('sort')
                            ->label(__('backend.sort'))
                            ->integer()
                            ->required()
                            ->default(0),
                    ]),
                Forms\Components\Textarea::make('remark')
                    ->label('备注')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }
}
