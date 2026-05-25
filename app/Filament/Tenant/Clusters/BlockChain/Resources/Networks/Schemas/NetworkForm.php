<?php

namespace App\Filament\Tenant\Clusters\BlockChain\Resources\Networks\Schemas;

use App\Enums\BlockChain\ChainType;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class NetworkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('网络名称')
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('网络类型')
                    ->options(ChainType::class)
                    ->required()
                    ->live(),
                Forms\Components\TextInput::make('rpc_url')
                    ->label('RPC地址')
                    ->url(),
                Forms\Components\TextInput::make('explorer_url')
                    ->label('浏览器地址')
                    ->url(),
                Forms\Components\TextInput::make('group_id')
                    ->label('群组 ID')
                    ->helperText('仅 FISCO BCOS 需要，默认为 1')
                    ->numeric()
                    ->default(1)
                    ->visible(fn (Get $get): bool => $get('type') === 'fisco'),
                Forms\Components\Toggle::make('status')
                    ->label(__('backend.status')),
                Section::make('SSL 证书（mTLS）')
                    ->columnSpanFull()
                    ->description('FISCO BCOS 等需要双向 SSL 认证的链，请粘贴 PEM 格式的证书内容')
                    ->schema([
                        Forms\Components\Textarea::make('ca_cert')
                            ->label('CA 证书（PEM）')
                            ->rows(5)
                            ->placeholder('-----BEGIN CERTIFICATE-----'."\n".'...'),
                        Forms\Components\Textarea::make('client_cert')
                            ->label('客户端证书（PEM）')
                            ->rows(5)
                            ->placeholder('-----BEGIN CERTIFICATE-----'."\n".'...'),
                        Forms\Components\Textarea::make('client_key')
                            ->label('客户端私钥（PEM）')
                            ->rows(5)
                            ->placeholder('-----BEGIN PRIVATE KEY-----'."\n".'...'),
                    ])
                    ->collapsed(true)
                    ->collapsible(),
            ]);
    }
}
