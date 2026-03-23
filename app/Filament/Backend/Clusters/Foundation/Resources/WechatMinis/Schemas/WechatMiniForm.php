<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\WechatMinis\Schemas;

use App\Filament\Forms\Components\TenantSelect;
use Filament\Forms;
use Filament\Schemas\Schema;

class WechatMiniForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TenantSelect::make()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('name')
                    ->label('小程序名称')
                    ->required(),
                Forms\Components\Toggle::make('status')
                    ->label(__('backend.status'))
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
