<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Vouchers\Schemas;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class VoucherInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Fieldset::make('基本信息')
                    ->columns()
                    ->schema([
                        Infolists\Components\TextEntry::make('tenant.name')
                            ->label(__('backend.tenant'))
                            ->badge(),
                    ]),
            ]);
    }
}
