<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Accounts\Schemas;

use Deldius\UserField\UserEntry;
use Filament\Infolists;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;

class AccountInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(5)
            ->components([
                UserEntry::make('user')
                    ->label('用户'),
                Infolists\Components\TextEntry::make('balance')
                    ->label('余额')
                    ->money('cny')
                    ->size(TextSize::Large),
                Infolists\Components\TextEntry::make('frozen_balance')
                    ->label('冻结余额')
                    ->money('cny')
                    ->size(TextSize::Large),
                Infolists\Components\TextEntry::make('points')
                    ->label('积分')
                    ->size(TextSize::Large),
                Infolists\Components\TextEntry::make('frozen_points')
                    ->label('冻结积分')
                    ->size(TextSize::Large),
            ]);
    }
}
