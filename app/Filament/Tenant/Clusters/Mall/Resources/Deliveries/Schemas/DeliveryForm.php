<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Deliveries\Schemas;

use App\Enums\Mall\DeliveryType;
use Filament\Forms;
use Filament\Schemas\Schema;

class DeliveryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('模板名称')
                    ->required()
                    ->maxLength(64),
                Forms\Components\Select::make('type')
                    ->label('计费方式')
                    ->options(DeliveryType::class)
                    ->required(),
                Forms\Components\TextInput::make('first')
                    ->label('首件/首重')
                    ->numeric()
                    ->default(1)
                    ->minValue(0),
                Forms\Components\TextInput::make('first_fee')
                    ->label('首费(元)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
                Forms\Components\TextInput::make('additional')
                    ->label('续件/续重')
                    ->numeric()
                    ->default(1)
                    ->minValue(0),
                Forms\Components\TextInput::make('additional_fee')
                    ->label('续费(元)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
                Forms\Components\TextInput::make('free_shipping_threshold')
                    ->label('包邮门槛(元)')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
                Forms\Components\Toggle::make('is_default')
                    ->label('设为默认模板')
                    ->helperText('设置为默认模板后，该租户的其他默认模板将被取消'),
                Forms\Components\Toggle::make('status')
                    ->label('启用状态')
                    ->default(true),
            ]);
    }
}
