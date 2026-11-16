<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Plans\Schemas;

use App\Enums\Finance\PlanTrigger;
use Filament\Forms;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

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
                    ->regex('/^[A-Za-z][A-Za-z0-9_]*$/')
                    ->unique(
                        ignorable: fn ($record) => $record,
                        modifyRuleUsing: fn (Unique $rule) => $rule->where('tenant_id', filament()->getTenant()?->getKey()),
                    )
                    ->readOnly(fn (string $operation): bool => $operation === 'edit')
                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                    ->helperText('唯一标识，以字母开头，只能包含字母、数字、下划线，创建后不可修改'),
                Forms\Components\Select::make('trigger')
                    ->label('触发方式')
                    ->options(PlanTrigger::class)
                    ->default(PlanTrigger::Manual),
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
