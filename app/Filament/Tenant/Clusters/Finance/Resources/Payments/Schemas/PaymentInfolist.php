<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Payments\Schemas;

use Filament\Infolists;
use Filament\Schemas\Schema;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Infolists\Components\TextEntry::make('no'),
            ]);
    }
}
