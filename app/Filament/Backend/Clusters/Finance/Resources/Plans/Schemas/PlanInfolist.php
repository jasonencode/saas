<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Plans\Schemas;

use Filament\Infolists;
use Filament\Schemas\Schema;

class PlanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Infolists\Components\TextEntry::make('name')
                    ->label('计划名称'),
                Infolists\Components\TextEntry::make('tenant.name')
                    ->label('租户'),
            ]);
    }
}
