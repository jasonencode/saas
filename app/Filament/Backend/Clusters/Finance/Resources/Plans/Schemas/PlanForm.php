<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Plans\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('计划名称')
                    ->required(),
                Forms\Components\TextInput::make('alias')
                    ->label('计划标识')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('描述')
                    ->columnSpanFull()
                    ->rows(3),
                Forms\Components\Toggle::make('status')
                    ->label(__('backend.status')),
                Forms\Components\TextInput::make('sort')
                    ->label(__('backend.sort'))
                    ->default(0)
                    ->integer(),
            ]);
    }
}
