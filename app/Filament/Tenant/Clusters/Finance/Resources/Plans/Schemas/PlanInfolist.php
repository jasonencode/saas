<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Plans\Schemas;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class PlanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Section::make('基础信息')
                    ->columnSpanFull()
                    ->columns()
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('计划名称'),
                        Infolists\Components\TextEntry::make('alias')
                            ->label('计划标识')
                            ->badge()
                            ->color('gray'),
                        Infolists\Components\TextEntry::make('description')
                            ->label('计划描述')
                            ->placeholder('-'),
                        Infolists\Components\IconEntry::make('status')
                            ->label(__('backend.status'))
                            ->boolean(),
                    ]),
            ]);
    }
}
