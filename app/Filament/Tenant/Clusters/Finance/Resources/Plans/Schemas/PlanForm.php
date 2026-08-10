<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Plans\Schemas;

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
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('alias')
                    ->label('计划标识')
                    ->required()
                    ->maxLength(64)
                    ->unique(ignorable: fn ($record) => $record)
                    ->readOnly(fn (string $operation): bool => $operation === 'edit')
                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                    ->helperText('唯一标识，创建后不可修改'),
                Forms\Components\Textarea::make('description')
                    ->label('计划描述')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('status')
                    ->label(__('backend.status'))
                    ->default(true),
                Forms\Components\TextInput::make('sort')
                    ->label(__('backend.sort'))
                    ->integer()
                    ->required()
                    ->default(0),
            ]);
    }
}
