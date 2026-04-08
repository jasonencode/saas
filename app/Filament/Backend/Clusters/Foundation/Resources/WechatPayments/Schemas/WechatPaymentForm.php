<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\WechatPayments\Schemas;

use App\Filament\Forms\Components\TenantSelect;
use App\Models\Foundation\Wechat;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class WechatPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TenantSelect::make()
                    ->afterStateUpdated(fn (Set $set) => $set('wechat_id', null))
                    ->columnSpanFull(),
                Forms\Components\Select::make('wechat_id')
                    ->label('关联微信平台')
                    ->required()
                    ->native(false)
                    ->options(fn (Get $get) => Wechat::where('tenant_id', $get('tenant_id'))->pluck('name', 'id')),
                Forms\Components\TextInput::make('name')
                    ->label('支付名称')
                    ->required(),
                Forms\Components\TextInput::make('mch_id')
                    ->label('商户号')
                    ->required(),
                Forms\Components\TextInput::make('secret')
                    ->label('支付秘钥V3')
                    ->required(),
                Forms\Components\Textarea::make('public_key')
                    ->label('API证书')
                    ->rows(10)
                    ->required()
                    ->helperText(fn () => new HtmlString('<span class="text-primary-500">apiclient_cert.pem</span> 文件的内容')),
                Forms\Components\Textarea::make('private_key')
                    ->label('API密钥')
                    ->rows(10)
                    ->required()
                    ->helperText(fn () => new HtmlString('<span class="text-primary-500">apiclient_key.pem</span> 文件的内容')),
                Forms\Components\Toggle::make('status')
                    ->label(__('backend.status'))
                    ->inline(false)
                    ->inlineLabel(false)
                    ->default(true),
                TextEntry::make('remark')
                    ->label('说明')
                    ->state(fn () => new HtmlString(sprintf(
                        '微信支付，需要配置支付安全地址为：<span class="text-primary-400">%s/</span>',
                        config('app.url'))))
                    ->columnSpanFull(),
            ]);
    }
}
