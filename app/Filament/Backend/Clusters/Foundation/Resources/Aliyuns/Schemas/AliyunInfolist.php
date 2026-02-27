<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\Aliyuns\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AliyunInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('账户名称'),
                TextEntry::make('app_id')
                    ->label('Access Key ID')
                    ->copyable(),
            ]);
    }
}
