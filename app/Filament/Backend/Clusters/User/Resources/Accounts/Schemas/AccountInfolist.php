<?php

namespace App\Filament\Backend\Clusters\User\Resources\Accounts\Schemas;

use Deldius\UserField\UserEntry;
use Filament\Infolists\Components\TextEntry;
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
                TextEntry::make('balance')
                    ->label('余额')
                    ->size(TextSize::Large),
                TextEntry::make('frozen_balance')
                    ->label('冻结余额')
                    ->size(TextSize::Large),
                TextEntry::make('points')
                    ->label('积分')
                    ->size(TextSize::Large),
                TextEntry::make('frozen_points')
                    ->label('冻结积分')
                    ->size(TextSize::Large),
            ]);
    }
}
