<?php

namespace App\Filament\Backend\Clusters\Finannce\Resources\PaymentOrders\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PaymentOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('no'),
            ]);
    }
}
