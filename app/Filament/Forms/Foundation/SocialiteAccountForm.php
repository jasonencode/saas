<?php

namespace App\Filament\Forms\Foundation;

use App\Enums\Foundation\SocialiteProvider;
use App\Filament\Forms\Components\TenantSelect;
use Filament\Forms;
use Filament\Schemas\Schema;

class SocialiteAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TenantSelect::make()
                    ->columnSpanFull(),
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
                    ->password()
                    ->revealable()
                    ->required(),
            ]);
    }
}
