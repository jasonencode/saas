<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Contracts\Schemas;

use App\Filament\Forms\Components\TenantSelect;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ContractForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TenantSelect::make(),
                Forms\Components\TextInput::make('name')
                    ->label('合约名称')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('deployer_id')
                    ->label('部署账户')
                    ->required()
                    ->native(false)
                    ->searchable()
                    ->preload()
                    ->live()
                    ->relationship(
                        name: 'deployer',
                        titleAttribute: 'address',
                        modifyQueryUsing: fn (Get $get, Builder $query) => $query
                            ->where('tenant_id', $get('tenant_id')),
                    ),
                Forms\Components\TextInput::make('parameter')
                    ->label('合约部署参数'),
                Forms\Components\Textarea::make('bytecode')
                    ->label('合约代码')
                    ->required()
                    ->rows(8),
                Forms\Components\Textarea::make('abi')
                    ->label('ABI')
                    ->required()
                    ->rows(8),
                Forms\Components\Textarea::make('original')
                    ->label('合约源代码')
                    ->rows(8),
                Forms\Components\Textarea::make('remark')
                    ->label('备注信息')
                    ->rows(8),
            ]);
    }
}
