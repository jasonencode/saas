<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Addresses\Schemas;

use App\Contracts\NetworkAdapterInterface;
use App\Models\BlockChain\ChainAddress;
use App\Models\BlockChain\Network;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class AddressForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('network_id')
                    ->label('主网')
                    ->relationship(
                        name: 'network',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->ofEnabled(),
                    )
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get, ?ChainAddress $record): void {
                        if ($record !== null) {
                            // 编辑模式：不做操作，字段均为只读
                            return;
                        }

                        // 创建模式：设置租户归属，自动生成私钥和地址
                        $networkId = $get('network_id');
                        if (blank($networkId)) {
                            return;
                        }

                        $network = Network::find($networkId);
                        if ($network === null) {
                            return;
                        }

                        $set('tenant_id', $network->tenant_id);

                        $adapter = self::resolveAdapter($network);
                        if ($adapter === null) {
                            return;
                        }

                        $privateKey = $adapter->generatePrivateKey();
                        $address = $adapter->getAddressFromPrivateKey($privateKey);

                        $set('private_key', $privateKey);
                        $set('address', $address);
                    }),
                Forms\Components\TextInput::make('name')
                    ->label('地址名称')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('private_key')
                    ->label('私钥')
                    ->readOnly()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (?ChainAddress $record): bool => $record === null)
                    ->suffixAction(
                        Action::make('generate_private_key')
                            ->icon(Heroicon::OutlinedArrowPath)
                            ->tooltip('重新生成私钥')
                            ->visible(fn (?ChainAddress $record): bool => $record === null)
                            ->action(function (Set $set, Get $get): void {
                                $networkId = $get('network_id');
                                if (blank($networkId)) {
                                    return;
                                }

                                $network = Network::find($networkId);
                                if ($network === null) {
                                    return;
                                }

                                $adapter = self::resolveAdapter($network);
                                if ($adapter === null) {
                                    return;
                                }

                                $privateKey = $adapter->generatePrivateKey();
                                $address = $adapter->getAddressFromPrivateKey($privateKey);

                                $set('private_key', $privateKey);
                                $set('address', $address);
                            })
                    ),
                Forms\Components\TextInput::make('address')
                    ->label('地址')
                    ->readOnly()
                    ->maxLength(64),
                Forms\Components\Hidden::make('tenant_id'),
                Forms\Components\Textarea::make('remark')
                    ->label('备注')
                    ->rows(3)
                    ->columnSpanFull()
                    ->nullable(),
            ]);
    }

    private static function resolveAdapter(Network $network): ?NetworkAdapterInterface
    {
        $adapterClass = $network->type->getAdapter();

        return new $adapterClass;
    }
}
