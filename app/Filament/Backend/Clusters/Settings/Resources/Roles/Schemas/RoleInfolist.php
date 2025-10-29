<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\Roles\Schemas;

use Filament\Infolists;
use Filament\Schemas\Schema;

class RoleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Infolists\Components\TextEntry::make('name')
                    ->label('角色名称')
                    ->columnSpanFull(),
            ]);
    }
}