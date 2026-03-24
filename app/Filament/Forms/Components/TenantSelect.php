<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;

class TenantSelect
{
    public static function make(string $label = '所属租户'): Select
    {
        return Select::make('tenant_id')
            ->label($label)
            ->relationship(
                name: 'tenant',
                titleAttribute: 'name',
                modifyQueryUsing: fn (Builder $query): Builder => $query->ofEnabled()
            )
            ->required()
            ->preload()
            ->searchable();
    }
}
