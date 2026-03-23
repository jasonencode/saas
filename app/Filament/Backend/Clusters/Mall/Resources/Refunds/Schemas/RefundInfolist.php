<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Refunds\Schemas;

use Filament\Infolists;
use Filament\Schemas\Schema;

class RefundInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Infolists\Components\TextEntry::make('no')
                    ->label('退款单号'),
                Infolists\Components\TextEntry::make('user.name')
                    ->label('User'),
                Infolists\Components\TextEntry::make('tenant.name')
                    ->label(__('backend.tenant'))
                    ->badge(),
                Infolists\Components\TextEntry::make('total')
                    ->numeric(),
                Infolists\Components\TextEntry::make('status')
                    ->badge(),
                Infolists\Components\TextEntry::make('refund_at')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('created_at')
                    ->placeholder('-'),
            ]);
    }
}

