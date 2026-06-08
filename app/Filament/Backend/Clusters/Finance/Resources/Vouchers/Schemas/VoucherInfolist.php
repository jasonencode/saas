<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Vouchers\Schemas;

use Filament\Infolists;
use Filament\Schemas\Schema;

class VoucherInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Infolists\Components\TextEntry::make('tenant.name')
                    ->label('租户'),
            ]);
    }
}
