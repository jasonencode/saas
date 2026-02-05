<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Refunds\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RefundInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('no'),
                TextEntry::make('user.id')
                    ->label('User'),
                TextEntry::make('tenant.name')
                    ->label('Store'),
                TextEntry::make('order_id')
                    ->numeric(),
                TextEntry::make('total')
                    ->numeric(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('refund_at')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->placeholder('-'),
            ]);
    }
}

