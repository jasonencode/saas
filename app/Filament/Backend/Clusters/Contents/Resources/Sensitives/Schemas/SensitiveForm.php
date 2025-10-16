<?php

namespace App\Filament\Backend\Clusters\Contents\Resources\Sensitives\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class SensitiveForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('keywords')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull()
                    ->label('敏感词'),
            ]);
    }
}
