<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\Aliyuns\Schemas;

use Filament\Infolists;
use Filament\Schemas\Schema;

class AliyunInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Infolists\Components\TextEntry::make('name')
                    ->label('账户名称'),
                Infolists\Components\TextEntry::make('app_id')
                    ->label('Access Key ID')
                    ->copyable(),
            ]);
    }
}
