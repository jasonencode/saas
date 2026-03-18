<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\Alipays\Schemas;

use App\Filament\Forms\Components\TenantSelect;
use Filament\Forms;
use Filament\Schemas\Schema;

class AlipayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TenantSelect::make()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('name')
                    ->label('配置名称')
                    ->required(),
                Forms\Components\Toggle::make('status')
                    ->label('状态')
                    ->inline(false)
                    ->inlineLabel(false)
                    ->default(true),
                Forms\Components\TextInput::make('app_id')
                    ->label('AppId')
                    ->required(),
                Forms\Components\Textarea::make('public_key')
                    ->label('应用公钥')
                    ->rows(8)
                    ->nullable(),
                Forms\Components\Textarea::make('private_key')
                    ->label('应用私钥')
                    ->rows(8)
                    ->nullable(),
                Forms\Components\Textarea::make('alipay_public_key')
                    ->label('支付宝公钥')
                    ->rows(8)
                    ->nullable(),
            ]);
    }
}
