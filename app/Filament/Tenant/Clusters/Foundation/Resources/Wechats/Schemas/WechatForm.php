<?php

namespace App\Filament\Tenant\Clusters\Foundation\Resources\Wechats\Schemas;

use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class WechatForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('微信名称')
                    ->required(),
                Forms\Components\Toggle::make('status')
                    ->label(__('backend.status'))
                    ->default(true),
                Forms\Components\TextInput::make('app_id')
                    ->label('AppId')
                    ->required(),
                Forms\Components\TextInput::make('app_secret')
                    ->label('API密钥')
                    ->required(),
                TextEntry::make('remark')
                    ->label('说明')
                    ->state(fn() => '公众平台服务号，需要配置网页授权域名为：'.config('app.url'))
                    ->columnSpanFull(),
            ]);
    }
}
