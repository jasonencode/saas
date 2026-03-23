<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Addresses\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;
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
                    ),
                Forms\Components\TextInput::make('name')
                    ->label('地址名称')
                    ->required(),

            ]);
    }
}
