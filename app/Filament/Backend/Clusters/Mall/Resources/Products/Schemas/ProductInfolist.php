<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Products\Schemas;

use Filament\Infolists;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Infolists\Components\TextEntry::make('name'),
            ]);
    }
}

