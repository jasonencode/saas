<?php

namespace App\Filament\Tenant\Clusters\Foundation\Resources\WechatMinis\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class WechatMiniForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('小程序名称')
                    ->required(),
                Forms\Components\Toggle::make('status')
                    ->label('状态')
                    ->inline(false)
                    ->inlineLabel(false)
                    ->default(true),
                Forms\Components\TextInput::make('app_id')
                    ->label('AppId')
                    ->required(),
                Forms\Components\TextInput::make('app_secret')
                    ->label('AppSecret')
                    ->required(),
            ]);
    }
}
