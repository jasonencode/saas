<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\ReturnAddresses\Schemas;

use App\Filament\Forms\Components\AddressSelect;
use Filament\Forms;
use Filament\Schemas\Schema;

class ReturnAddressForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('收件人')
                    ->required(),
                Forms\Components\TextInput::make('phone')
                    ->label('联系电话')
                    ->required(),
                AddressSelect::make(),
                Forms\Components\Toggle::make('is_default')
                    ->label('默认地址'),
                Forms\Components\Toggle::make('status')
                    ->label(__('backend.status')),
                Forms\Components\Textarea::make('remark')
                    ->label('备注信息')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }
}
