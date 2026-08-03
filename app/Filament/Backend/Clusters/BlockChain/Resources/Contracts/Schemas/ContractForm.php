<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Contracts\Schemas;

use App\Enums\BlockChain\ContractType;
use App\Filament\Forms\Components\TenantSelect;
use App\Models\BlockChain\ChainAddress;
use App\Models\BlockChain\Contract;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class ContractForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TenantSelect::make()
                    ->live(),
                Forms\Components\TextInput::make('name')
                    ->label('合约名称')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('type')
                    ->label('合约类型')
                    ->options(ContractType::class)
                    ->default(ContractType::CUSTOM->value)
                    ->required(),
                Forms\Components\Select::make('network_id')
                    ->label('主网')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->relationship(
                        name: 'network',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Get $get, EloquentBuilder $query) => $query
                            ->ofEnabled()
                            ->where('tenant_id', $get('tenant_id')),
                    )
                    ->live()
                    ->afterStateUpdated(static function (Set $set, ?Contract $record, mixed $state): void {
                        if ($record !== null && (string) $record->network_id === (string) $state) {
                            return;
                        }

                        $set('deployer_id', null);
                    }),
                Forms\Components\Select::make('deployer_id')
                    ->label('部署账户')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(static fn (Get $get): array => ChainAddress::when(
                        filled($get('network_id')),
                        static fn (EloquentBuilder $query) => $query
                            ->where('network_id', $get('network_id'))
                            ->where('tenant_id', $get('tenant_id')),
                        static fn (EloquentBuilder $query) => $query->whereRaw('1 = 0')
                    )
                        ->orderBy('address')
                        ->pluck('address', 'id')
                        ->all()
                    )
                    ->helperText('请先选择主网，再选择该主网下的部署地址')
                    ->exists(
                        modifyRuleUsing: static fn ($rule, Get $get) => $rule->where(static function (QueryBuilder $query) use ($get): void {
                            $query->where('network_id', $get('network_id'));
                        })
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
