<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Systems\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SystemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('用户名称')
                    ->required(),
            ]);
    }
}
