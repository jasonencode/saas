<?php

namespace App\Filament\Tables\Filters;

use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class TenantFilter
{
    public static function make(): SelectFilter
    {
        return SelectFilter::make('tenant_id')
            ->label(__('backend.tenant'))
            ->relationship(
                name: 'tenant',
                titleAttribute: 'name',
                modifyQueryUsing: fn (Builder $query) => $query->ofEnabled()
            )
            ->searchable()
            ->preload();
    }
}
