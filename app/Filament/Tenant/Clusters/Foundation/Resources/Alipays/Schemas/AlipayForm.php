<?php

namespace App\Filament\Tenant\Clusters\Foundation\Resources\Alipays\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class AlipayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('配置名称')
                    ->required(),
                Forms\Components\Toggle::make('status')
                    ->label(__('backend.status'))
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
