<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Networks\Schemas;

use App\Enums\ChainType;
use App\Filament\Forms\Components\TenantSelect;
use Filament\Forms;
use Filament\Schemas\Schema;

class NetworkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TenantSelect::make(),
                Forms\Components\TextInput::make('name')
                    ->label('网络名称')
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label('网络类型')
                    ->options(ChainType::class)
                    ->required(),
                Forms\Components\TextInput::make('rpc_url')
                    ->label('RPC地址')
                    ->url(),
                Forms\Components\TextInput::make('explorer_url')
                    ->label('浏览器地址')
                    ->url(),
                Forms\Components\Toggle::make('status')
                    ->label('状态'),
            ]);
    }
}
