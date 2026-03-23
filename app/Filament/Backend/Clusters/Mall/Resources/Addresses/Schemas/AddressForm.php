<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Addresses\Schemas;

use App\Filament\Forms\Components\AddressSelect;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;

class AddressForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('user_id')
                    ->label('用户')
                    ->options(fn () => User::query()->pluck('username', 'id'))
                    ->searchable()
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('name')
                    ->label('联系人')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('mobile')
                    ->label('手机号')
                    ->required()
                    ->maxLength(20),
                AddressSelect::make(),
                Forms\Components\Toggle::make('is_default')
                    ->label('设为默认')
                    ->default(false),
            ]);
    }
}
