<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\Wechats\Schemas;

use App\Filament\Forms\Components\TenantSelect;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class WechatForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TenantSelect::make()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('name')
                    ->label('微信名称')
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
                    ->label('API密钥')
                    ->required(),
                TextEntry::make('remark')
                    ->label('说明')
                    ->state(new HtmlString(sprintf(
                        '公众平台服务号，需要配置网页授权域名为：<span class="text-primary-400">%s</span>',
                        config('app.url'))))
                    ->columnSpanFull(),
            ]);
    }
}
