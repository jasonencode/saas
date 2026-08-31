<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Roles\Schemas;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class RoleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Fieldset::make('基本信息')
                    ->columns()
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('角色名称')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
