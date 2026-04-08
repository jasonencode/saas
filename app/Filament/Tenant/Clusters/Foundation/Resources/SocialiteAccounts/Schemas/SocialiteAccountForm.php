<?php

namespace App\Filament\Tenant\Clusters\Foundation\Resources\SocialiteAccounts\Schemas;

use App\Enums\Foundation\SocialiteProvider;
use Filament\Forms;
use Filament\Schemas\Schema;

class SocialiteAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('provider')
                    ->label('平台类型')
                    ->options(SocialiteProvider::class)
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('账户名称')
                    ->required(),
                Forms\Components\TextInput::make('app_key')
                    ->label('APP_KEY')
                    ->required(),
                Forms\Components\TextInput::make('app_secret')
                    ->label('APP_SECRET')
                    ->required(),
            ]);
    }
}
