<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Contents\Schemas;

use Filament\Infolists;
use Filament\Schemas\Schema;

class ContentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Infolists\Components\TextEntry::make('title')
                    ->label('标题'),
            ]);
    }
}