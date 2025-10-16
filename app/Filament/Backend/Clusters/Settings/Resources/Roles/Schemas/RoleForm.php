<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\Roles\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('角色名称')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('描述')
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }
}
